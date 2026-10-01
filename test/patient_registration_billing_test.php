<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/PatientRegistrationBillingService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/VisitService.php';

function assertRegistrationBilling(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertRegistrationBilling(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Registration billing tests are not isolated from the live database.'
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
foreach ([30, 33, 44, 78] as $migration) {
    $files = glob(__DIR__ . '/../database/migrations/' . sprintf('%03d', $migration) . '_*_up.sql');
    assertRegistrationBilling(isset($files[0]), 'Missing migration ' . $migration . '.');
    $manager->apply($files[0], $migration);
}

$users = [];
$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('dev_reception','dev_accounts')
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}
assertRegistrationBilling(isset($users['dev_reception'], $users['dev_accounts']), 'Fixture users are missing.');

$service = new PatientRegistrationBillingService($pdo);
$patientService = new PatientService($pdo);

$result = $service->createInitialRegistration([
    'first_name' => 'Pending',
    'middle_name' => 'Payment',
    'last_name' => 'Patient',
    'phone' => '08030007878',
    'registration_type' => 'Normal',
], $users['dev_reception']);

assertRegistrationBilling(($result['success'] ?? false) === true, 'Initial registration billing request failed.');
$patientId = (int)$result['patient_id'];
$patient = $patientService->getPatientById($patientId);
assertRegistrationBilling($patient !== null, 'Provisional patient was not created.');
assertRegistrationBilling((string)($patient['hospital_number'] ?? '') === '', 'Hospital number should not be generated before payment.');
assertRegistrationBilling((string)($patient['registration_status'] ?? '') === 'PendingPayment', 'Patient should be pending payment.');
assertRegistrationBilling((float)$result['amount'] === 30000.0, 'Normal registration fee should be 30000.');

$gate = $patientService->getRegistrationGateStatus($patientId);
assertRegistrationBilling(empty($gate['can_create_encounter']), 'Pending registration should block encounters.');

$paid = $service->markPaid((int)$result['billing_request_id'], $users['dev_accounts'], 'Test payment');
assertRegistrationBilling(($paid['success'] ?? false) === true, 'Accounts could not clear registration payment.');
$patient = $patientService->getPatientById($patientId);
assertRegistrationBilling((string)($patient['hospital_number'] ?? '') !== '', 'Hospital number should be generated after payment.');
assertRegistrationBilling((string)($patient['registration_status'] ?? '') === 'Active', 'Patient should be active after payment.');
assertRegistrationBilling((string)($patient['registration_valid_until'] ?? '') !== '', 'Registration validity date should be set.');
$registrationPayments = $service->listRegistrationPaymentsFiltered([
    'patient_name' => 'Pending',
    'payment_reference' => 'Test payment',
], $users['dev_accounts']);
assertRegistrationBilling($registrationPayments !== [], 'Paid registration clearances should appear in billing payment history.');
assertRegistrationBilling(
    (int)($registrationPayments[0]['patient_id'] ?? 0) === $patientId,
    'Paid registration clearance should be tied to the patient.'
);
$patientRegistrationHistory = $service->listRequests(['patient_id' => $patientId, 'status' => '']);
assertRegistrationBilling(count($patientRegistrationHistory) >= 1, 'Patient registration billing history should be filterable by patient.');

$pdo->prepare("UPDATE patients SET registration_valid_until = DATE_SUB(CURDATE(), INTERVAL 1 DAY) WHERE id = :id")
    ->execute([':id' => $patientId]);
$expired = $patientService->getRegistrationGateStatus($patientId);
assertRegistrationBilling(($expired['status'] ?? '') === 'Expired', 'Expired registration should be detected.');

$renewal = $service->ensureRenewalRequest($patientId, $users['dev_reception']);
assertRegistrationBilling(($renewal['success'] ?? false) === true, 'Renewal request should be created.');
assertRegistrationBilling((float)($renewal['request']['amount'] ?? 0) === 20000.0, 'Monthly renewal fee should be 20000.');

$worklistContents = file_get_contents(__DIR__ . '/../modules/visits/department_worklist.php');
assertRegistrationBilling(
    is_string($worklistContents)
        && str_contains($worklistContents, 'patient_registration_billing_requests')
        && str_contains($worklistContents, 'registration_request_pay.php'),
    'Accounts department worklist should expose pending registration billing requests.'
);

$sidebarContents = file_get_contents(__DIR__ . '/../layouts/sidebar.php');
assertRegistrationBilling(
    is_string($sidebarContents)
        && str_contains($sidebarContents, 'patient_registration_billing_requests'),
    'Accounts department worklist badge should include registration billing requests.'
);

$billingIndexContents = file_get_contents(__DIR__ . '/../modules/billing/index.php');
assertRegistrationBilling(
    is_string($billingIndexContents)
        && str_contains($billingIndexContents, 'Registration Payment Clearances')
        && str_contains($billingIndexContents, 'Registration Billing Matches'),
    'Billing home should show registration payment clearances and search matches.'
);

$financialReportContents = file_get_contents(__DIR__ . '/../modules/reports/financial.php');
assertRegistrationBilling(
    is_string($financialReportContents)
        && str_contains($financialReportContents, 'Registration Payments')
        && str_contains($financialReportContents, 'patient_registration_billing_requests'),
    'Financial report should include cleared registration payments.'
);

$patientProfileContents = file_get_contents(__DIR__ . '/../modules/patients/view.php');
assertRegistrationBilling(
    is_string($patientProfileContents)
        && str_contains($patientProfileContents, 'renew_registration.php')
        && str_contains($patientProfileContents, 'Create Renewal Payment Request')
        && str_contains($patientProfileContents, 'registration_request_pay.php'),
    'Patient profile should expose registration renewal and payment actions.'
);

$registrationPaymentHandlerContents = file_get_contents(__DIR__ . '/../modules/billing/registration_request_pay.php');
assertRegistrationBilling(
    is_string($registrationPaymentHandlerContents)
        && str_contains($registrationPaymentHandlerContents, 'return_to')
        && str_contains($registrationPaymentHandlerContents, 'patients\\/view\\.php\\?id='),
    'Registration payment handler should safely return to patient profile.'
);

fwrite(STDOUT, 'PASS: Patient registration billing workflow passed.' . PHP_EOL);
