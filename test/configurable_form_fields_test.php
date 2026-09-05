<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/ConfigurableFormService.php';
require_once __DIR__ . '/../services/NursingService.php';
require_once __DIR__ . '/../services/PermissionService.php';

function assertConfiguredForms(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertConfiguredForms(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Configurable form tests are not isolated from the live database.'
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/070_configurable_form_fields_up.sql', 70);
$manager->apply(__DIR__ . '/../database/migrations/071_additional_configurable_form_targets_up.sql', 71);

$users = [];
$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('walter','dev_nurse','dev_doctor')
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}

foreach (['walter', 'dev_nurse', 'dev_doctor'] as $username) {
    assertConfiguredForms(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['walter'];
$nurse = $users['dev_nurse'];
$doctor = $users['dev_doctor'];

$permissionService = new PermissionService($pdo);
$service = new ConfigurableFormService($pdo, $permissionService);

assertConfiguredForms($service->tablesAvailable(), 'Configurable form tables should be available.');

$pdo->exec("
    UPDATE form_fields ff
    INNER JOIN form_definitions fd ON fd.id = ff.form_definition_id
    SET ff.is_active = 0, ff.is_required = 0
    WHERE fd.form_key = 'nursing_assessment'
");

assertConfiguredForms($permissionService->canManageConfigurableForms($admin), 'Super Administrator should manage configurable forms.');
assertConfiguredForms(!$permissionService->canManageConfigurableForms($doctor), 'Doctor should not manage configurable forms by default.');

$definitions = $service->listDefinitions($admin);
assertConfiguredForms(count($definitions) >= 8, 'Expected configurable form definitions.');
assertConfiguredForms($service->listDefinitions($doctor) === [], 'Non-admin should not list admin configurable form definitions.');

$inactiveFields = $service->listFields('nursing_assessment', true);
assertConfiguredForms($inactiveFields === [], 'Seeded Nursing configured fields should be inactive until selected.');

$allFields = $service->listFields('nursing_assessment', false);
assertConfiguredForms(count($allFields) >= 3, 'Expected seeded Nursing configured fields.');
$fieldIds = array_column($allFields, 'id', 'field_key');
assertConfiguredForms(isset($fieldIds['mental_status']), 'Missing Mental Status configured field.');

$result = $service->saveFieldConfig('nursing_assessment', [
    'fields' => [
        (int)$fieldIds['mental_status'] => [
            'field_label' => 'Mental Status',
            'field_type' => 'textarea',
            'is_required' => '1',
            'sort_order' => '10',
            'is_active' => '1',
            'options' => '',
        ],
    ],
], $admin);
assertConfiguredForms(($result['success'] ?? false) === true, 'Admin should activate Mental Status.');

$activeFields = $service->listFields('nursing_assessment', true);
assertConfiguredForms(count($activeFields) === 1, 'Exactly one Nursing configured field should be active.');

$patientId = (int)$pdo->query("SELECT id FROM patients WHERE hospital_number = 'DEV-PATIENT-0001' LIMIT 1")->fetchColumn();
$departmentId = (int)$nurse['department_id'];
$pdo->prepare("
    INSERT INTO visits (
        visit_number, patient_id, visit_date, visit_type, current_department_id,
        current_department_received_status, visit_status, created_by
    ) VALUES (
        :visit_number, :patient_id, NOW(), 'Outpatient', :department_id,
        'Received', 'Nursing', :created_by
    )
")->execute([
    ':visit_number' => 'CFG-FORM-' . uniqid(),
    ':patient_id' => $patientId,
    ':department_id' => $departmentId,
    ':created_by' => (int)$nurse['id'],
]);
$visitId = (int)$pdo->lastInsertId();

$nursingService = new NursingService($pdo, null, null, $permissionService);
$nursingResult = $nursingService->create([
    'visit_id' => $visitId,
    'patient_id' => $patientId,
    'general_condition' => 'Stable',
    'nursing_observation' => 'Comfortable',
], $nurse);
assertConfiguredForms(($nursingResult['success'] ?? false) === true, 'Nursing fixture record should save.');

$missingConfigured = $service->saveResponse(
    'nursing_assessment',
    $patientId,
    $visitId,
    'Nursing Assessment',
    (int)$nursingResult['nursing_assessment_id'],
    ['configured_fields' => ['mental_status' => '']],
    $nurse
);
assertConfiguredForms(($missingConfigured['success'] ?? false) === false, 'Required configured field should be enforced.');

$savedConfigured = $service->saveResponse(
    'nursing_assessment',
    $patientId,
    $visitId,
    'Nursing Assessment',
    (int)$nursingResult['nursing_assessment_id'],
    ['configured_fields' => ['mental_status' => 'Alert and oriented']],
    $nurse
);
assertConfiguredForms(($savedConfigured['success'] ?? false) === true, 'Configured response should save.');

$values = $service->getResponseValueMap(
    'nursing_assessment',
    'Nursing Assessment',
    (int)$nursingResult['nursing_assessment_id']
);
assertConfiguredForms(($values['mental_status'] ?? '') === 'Alert and oriented', 'Configured response value should round-trip.');

echo 'Configurable form fields regression passed.' . PHP_EOL;
