<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/AccountsService.php';
require_once __DIR__ . '/../services/BillingService.php';
require_once __DIR__ . '/../services/LaboratoryService.php';
require_once __DIR__ . '/../services/PermissionService.php';

function assertBilling(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireBillingSuccess(array $result, string $label): array
{
    assertBilling(($result['success'] ?? false) === true, $label . ': ' . implode(' ', (array)($result['errors'] ?? [])));
    return $result;
}

function createBillingEncounter(PDO $pdo, array $actor, int $patientId, int $departmentId, string $suffix): int
{
    $stmt = $pdo->prepare('
        INSERT INTO visits (
            visit_number, patient_id, visit_date, visit_type, current_department_id,
            attending_doctor_id, current_department_received_status, visit_status, created_by
        ) VALUES (
            :visit_number, :patient_id, NOW(), :visit_type, :department_id,
            :attending_doctor_id, :received_status, :visit_status, :created_by
        )
    ');
    $stmt->execute([
        ':visit_number' => 'BIL-' . $suffix,
        ':patient_id' => $patientId,
        ':visit_type' => 'Outpatient',
        ':department_id' => $departmentId,
        ':attending_doctor_id' => (int)($actor['id'] ?? 0),
        ':received_status' => 'Received',
        ':visit_status' => 'Doctor',
        ':created_by' => (int)($actor['id'] ?? 0),
    ]);

    return (int)$pdo->lastInsertId();
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertBilling(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Phase 4.4 tests are not isolated from the live database.'
);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL .
    'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/030_phase4_accounts_price_catalogue_up.sql', 30);
$manager->apply(__DIR__ . '/../database/migrations/031_phase4_store_inventory_up.sql', 31);
$manager->apply(__DIR__ . '/../database/migrations/032_phase4_pharmacy_up.sql', 32);
$manager->apply(__DIR__ . '/../database/migrations/025_phase3_laboratory_up.sql', 25);
$manager->apply(__DIR__ . '/../database/migrations/026_phase3_laboratory_result_details_up.sql', 26);
$manager->apply(__DIR__ . '/../database/migrations/033_phase4_billing_up.sql', 33);
$manager->apply(__DIR__ . '/../database/migrations/044_billing_requests_up.sql', 44);
$manager->apply(__DIR__ . '/../database/migrations/072_billing_discounts_up.sql', 72);
$manager->apply(__DIR__ . '/../database/migrations/076_emergency_department_up.sql', 76);

$pdo->exec("DELETE FROM billing_requests WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
$pdo->exec("DELETE FROM billing_discounts WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
$pdo->exec("DELETE FROM payments WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
$pdo->exec("DELETE FROM invoices WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
$pdo->exec("DELETE FROM patient_charges WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
$pdo->exec("DELETE lr FROM laboratory_results lr INNER JOIN laboratory_requests lq ON lq.id = lr.laboratory_request_id INNER JOIN visits v ON v.id = lq.visit_id WHERE v.visit_number LIKE 'BIL-%'");
$pdo->exec("DELETE lq FROM laboratory_requests lq INNER JOIN visits v ON v.id = lq.visit_id WHERE v.visit_number LIKE 'BIL-%'");
$pdo->exec("DELETE FROM encounter_events WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
$pdo->exec("DELETE FROM audit_logs WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
$pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'BIL-%'");
$pdo->exec("DELETE FROM billable_items WHERE item_code LIKE 'BIL-%'");

$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('walter','dev_accounts','dev_doctor','dev_nurse','dev_laboratory')
")->fetchAll(PDO::FETCH_ASSOC);
$users = [];
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}

foreach (['walter', 'dev_accounts', 'dev_doctor', 'dev_nurse', 'dev_laboratory'] as $username) {
    assertBilling(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['walter'];
$accounts = $users['dev_accounts'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];
$laboratory = $users['dev_laboratory'];

$doctorDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Doctor' LIMIT 1")->fetchColumn();
assertBilling($doctorDepartmentId > 0, 'Doctor department is missing.');
$nursingDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Nursing' LIMIT 1")->fetchColumn();
assertBilling($nursingDepartmentId > 0, 'Nursing department is missing.');

$patientId = (int)$pdo->query("SELECT id FROM patients WHERE hospital_number = 'DEV-PATIENT-0001' LIMIT 1")->fetchColumn();
$patientId2 = (int)$pdo->query("SELECT id FROM patients WHERE hospital_number = 'DEV-PATIENT-0002' LIMIT 1")->fetchColumn();
assertBilling($patientId > 0 && $patientId2 > 0, 'Billing fixture patients are missing.');

$accountsService = new AccountsService($pdo, null, new PermissionService($pdo));
$billingService = new BillingService($pdo, null, null, new PermissionService($pdo));
$permissionService = new PermissionService($pdo);

try {
    assertBilling($permissionService->canViewBilling($doctor), 'Doctor should be able to view billing.');
    assertBilling($permissionService->canViewBilling($nurse), 'Nurse should be able to view billing.');
    assertBilling($permissionService->canCreatePatientCharge($accounts), 'Accounts should be able to create charges.');
    assertBilling(!$permissionService->canCreatePatientCharge($doctor), 'Doctor should not create charges.');
    assertBilling($permissionService->canCreateBillingRequest($doctor), 'Doctor should be able to create billing requests.');
    assertBilling($permissionService->canCreateBillingRequest($nurse), 'Nurse should be able to create billing requests.');
    assertBilling($permissionService->canViewBillingRequests($accounts), 'Accounts should view billing requests.');
    assertBilling($permissionService->canReviewBillingRequest($accounts), 'Accounts should review billing requests.');
    assertBilling(!$permissionService->canReviewBillingRequest($doctor), 'Doctor should not review billing requests.');
    assertBilling(!$permissionService->canRecordPayment($nurse), 'Nurse should not record payments.');
    assertBilling($permissionService->canViewReceipts($accounts), 'Accounts should view receipts.');
    assertBilling($permissionService->canApplyBillingDiscount($admin), 'Super Administrator should apply discounts.');
    assertBilling($permissionService->canCancelBillingDiscount($admin), 'Super Administrator should cancel discounts.');
    assertBilling(!$permissionService->canApplyBillingDiscount($accounts), 'Accounts should not apply discounts by default.');

    assertBilling(str_contains(file_get_contents(__DIR__ . '/../layouts/sidebar.php'), '/modules/billing/index.php'), 'Sidebar missing Billing destination.');
    assertBilling(str_contains(file_get_contents(__DIR__ . '/../modules/visits/workspace.php'), 'BillingService.php'), 'Workspace missing billing integration.');
    assertBilling(str_contains(file_get_contents(__DIR__ . '/../modules/visits/partials/tabs/billing.php'), 'tab-billing'), 'Billing tab is not wired.');

    $consultationItem = requireBillingSuccess($accountsService->createItem([
        'item_code' => 'BIL-SRV-001',
        'item_name' => 'General Consultation',
        'item_type' => 'Service',
        'department_id' => $doctorDepartmentId,
        'description' => 'General consultation fee.',
        'unit_price' => 2000,
        'unit' => '',
        'is_active' => 1,
    ], $admin), 'Create consultation item');
    $consultationItemId = (int)$consultationItem['billable_item_id'];

    $radiologyItem = requireBillingSuccess($accountsService->createItem([
        'item_code' => 'BIL-SRV-002',
        'item_name' => 'Chest X-Ray',
        'item_type' => 'Service',
        'department_id' => (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'X-Ray' LIMIT 1")->fetchColumn(),
        'description' => 'Chest imaging fee.',
        'unit_price' => 1500,
        'unit' => '',
        'is_active' => 1,
    ], $admin), 'Create radiology item');
    $radiologyItemId = (int)$radiologyItem['billable_item_id'];

    $laboratoryItem = requireBillingSuccess($accountsService->createItem([
        'item_code' => 'BIL-SRV-003',
        'item_name' => 'Full Blood Count',
        'item_type' => 'Service',
        'department_id' => (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Laboratory' LIMIT 1")->fetchColumn(),
        'description' => 'Laboratory test fee.',
        'unit_price' => 1200,
        'unit' => '',
        'is_active' => 1,
    ], $admin), 'Create laboratory item');
    $laboratoryItemId = (int)$laboratoryItem['billable_item_id'];

    $laboratoryExtraItem = requireBillingSuccess($accountsService->createItem([
        'item_code' => 'BIL-SRV-004',
        'item_name' => 'Sample Collection Fee',
        'item_type' => 'Service',
        'department_id' => (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Laboratory' LIMIT 1")->fetchColumn(),
        'description' => 'Laboratory sample collection fee.',
        'unit_price' => 500,
        'unit' => '',
        'is_active' => 1,
    ], $admin), 'Create laboratory extra item');
    $laboratoryExtraItemId = (int)$laboratoryExtraItem['billable_item_id'];

    $visitId = createBillingEncounter($pdo, $admin, $patientId, $doctorDepartmentId, '001');
    $visitId2 = createBillingEncounter($pdo, $admin, $patientId2, $doctorDepartmentId, '002');

    $pendingRequest = requireBillingSuccess($billingService->createBillingRequest([
        'visit_id' => $visitId,
        'department_id' => $doctorDepartmentId,
        'source_module' => 'Consultation',
        'source_record_id' => 9001,
        'description' => 'General consultation completed; please bill consultation fee.',
        'suggested_billable_item_id' => null,
        'quantity' => 1,
    ], $doctor), 'Doctor creates billing request');
    $pendingRequestId = (int)$pendingRequest['billing_request_id'];

    $billingRequestRows = $billingService->listBillingRequests(['visit_id' => $visitId], $accounts);
    assertBilling(count($billingRequestRows) === 1, 'Accounts did not see billing request.');
    assertBilling((string)$billingRequestRows[0]['status'] === 'Charged', 'Billing request should be automatically charged.');
    assertBilling((int)($billingRequestRows[0]['patient_charge_id'] ?? 0) > 0, 'Auto-charged billing request is missing patient charge link.');
    assertBilling(abs((float)$billingService->getEncounterBalance($visitId, $accounts)['total_charges'] - 2000.0) < 0.01, 'Auto-charged billing request did not update charge totals.');
    $manualChargeId = (int)$billingRequestRows[0]['patient_charge_id'];

    $manualCharge = requireBillingSuccess($billingService->createCharge([
        'visit_id' => $visitId,
        'billable_item_id' => $consultationItemId,
        'quantity' => 1,
        'description' => 'Manual consultation fee.',
        'source_module' => 'Billing',
    ], $accounts), 'Manual charge');
    $chargeCountAfterManual = (int)$pdo->query("SELECT COUNT(*) FROM patient_charges WHERE visit_id = {$visitId}")->fetchColumn();
    assertBilling($chargeCountAfterManual === 2, 'Manual charge was not recorded alongside the automatic request charge.');

    $requestCharge = $billingService->chargeBillingRequest([
        'billing_request_id' => $pendingRequestId,
        'billable_item_id' => $consultationItemId,
        'quantity' => 1,
        'description' => 'Consultation fee from billing request.',
        'notes' => 'Approved by Accounts.',
    ], $accounts);
    assertBilling(($requestCharge['success'] ?? false) === false, 'Automatically charged billing request should not be converted manually.');

    $requestAfterCharge = $billingService->getBillingRequestById($pendingRequestId, $accounts);
    assertBilling($requestAfterCharge !== null && (string)$requestAfterCharge['status'] === 'Charged', 'Billing request was not marked Charged.');
    assertBilling((int)$requestAfterCharge['patient_charge_id'] === $manualChargeId, 'Billing request charge link was not stored.');

    $duplicateRequestCharge = $billingService->chargeBillingRequest([
        'billing_request_id' => $pendingRequestId,
        'billable_item_id' => $consultationItemId,
        'quantity' => 1,
        'description' => 'Duplicate should fail.',
    ], $accounts);
    assertBilling(($duplicateRequestCharge['success'] ?? false) === false, 'Charged billing request was converted twice.');

    $cancelRequest = requireBillingSuccess($billingService->createBillingRequest([
        'visit_id' => $visitId2,
        'department_id' => $nursingDepartmentId,
        'source_module' => 'Nursing',
        'description' => 'Nursing supply used; please review.',
        'quantity' => 2,
    ], $nurse), 'Nurse creates billing request');
    requireBillingSuccess(
        $billingService->cancelBillingRequest((int)$cancelRequest['billing_request_id'], 'Not billable after review.', $accounts),
        'Cancel billing request'
    );
    $cancelledRequest = $billingService->getBillingRequestById((int)$cancelRequest['billing_request_id'], $accounts);
    assertBilling($cancelledRequest !== null && (string)$cancelledRequest['status'] === 'Cancelled', 'Billing request was not cancelled.');

    $doctorReviewAttempt = $billingService->chargeBillingRequest([
        'billing_request_id' => (int)$cancelRequest['billing_request_id'],
        'billable_item_id' => $consultationItemId,
        'quantity' => 1,
    ], $doctor);
    assertBilling(($doctorReviewAttempt['success'] ?? false) === false, 'Doctor should not review billing requests.');

    $snapshotCharge = requireBillingSuccess($billingService->createChargeFromBillableItem(
        $visitId,
        $radiologyItemId,
        1,
        'Radiology',
        77,
        'Chest X-ray requested.',
        $accounts
    ), 'Create source charge');
    $snapshotChargeId = (int)$snapshotCharge['patient_charge_id'];

    $duplicateSource = requireBillingSuccess($billingService->createChargeFromBillableItem(
        $visitId,
        $radiologyItemId,
        1,
        'Radiology',
        77,
        'Chest X-ray requested.',
        $accounts
    ), 'Duplicate source charge');
    assertBilling((int)$duplicateSource['patient_charge_id'] === $snapshotChargeId, 'Duplicate source charge was not deduplicated.');
    assertBilling((int)$pdo->query("SELECT COUNT(*) FROM patient_charges WHERE visit_id = {$visitId}")->fetchColumn() === 3, 'Duplicate source charge inserted a second row.');

    $chargeRow = $pdo->query("SELECT amount FROM patient_charges WHERE id = {$manualChargeId}")->fetch(PDO::FETCH_ASSOC);
    assertBilling(abs((float)$chargeRow['amount'] - 2000.0) < 0.01, 'Manual charge amount is incorrect.');

    $updateItem = requireBillingSuccess($accountsService->updateItem($consultationItemId, [
        'item_code' => 'BIL-SRV-001',
        'item_name' => 'General Consultation',
        'item_type' => 'Service',
        'department_id' => $doctorDepartmentId,
        'description' => 'Updated price.',
        'unit_price' => 2500,
        'unit' => '',
        'is_active' => 1,
    ], $admin), 'Update price catalogue item');
    assertBilling(($updateItem['success'] ?? false) === true, 'Price catalogue update failed.');

    $chargeRowAfterPriceChange = $pdo->query("SELECT amount FROM patient_charges WHERE id = {$manualChargeId}")->fetch(PDO::FETCH_ASSOC);
    assertBilling(abs((float)$chargeRowAfterPriceChange['amount'] - 2000.0) < 0.01, 'Charge amount changed after catalogue price update.');

    $invoice = requireBillingSuccess($billingService->createInvoice($visitId, $accounts), 'Create invoice');
    $invoiceRow = $billingService->getInvoiceByVisit($visitId, $accounts);
    assertBilling($invoiceRow !== null, 'Invoice not found after creation.');
    assertBilling(abs((float)$invoiceRow['total_amount'] - 5500.0) < 0.01, 'Invoice total is incorrect.');
    assertBilling(abs((float)$invoiceRow['balance_due'] - 5500.0) < 0.01, 'Invoice balance is incorrect.');
    assertBilling((string)$invoiceRow['status'] === 'Unpaid', 'Invoice status should be Unpaid before payment.');

    $invoiceSearch = $billingService->listInvoices(['invoice_number' => $invoiceRow['invoice_number']], $accounts);
    assertBilling(count($invoiceSearch) === 1, 'Invoice search did not return the expected row.');

    $cancelCharge = requireBillingSuccess($billingService->cancelCharge($snapshotChargeId, $accounts), 'Cancel charge');
    assertBilling(($cancelCharge['success'] ?? false) === true, 'Charge cancellation failed.');

    $invoiceAfterCancel = $billingService->getInvoiceByVisit($visitId, $accounts);
    assertBilling(abs((float)$invoiceAfterCancel['total_amount'] - 4000.0) < 0.01, 'Invoice total did not refresh after charge cancellation.');

    $invalidDiscount = $billingService->applyBillingDiscount([
        'visit_id' => $visitId,
        'invoice_id' => (int)$invoiceAfterCancel['id'],
        'discount_percent' => 20,
        'reason' => 'Invalid discount should fail.',
    ], $admin);
    assertBilling(($invalidDiscount['success'] ?? false) === false, 'Unsupported discount percentage was accepted.');

    $accountsDiscount = $billingService->applyBillingDiscount([
        'visit_id' => $visitId,
        'invoice_id' => (int)$invoiceAfterCancel['id'],
        'discount_percent' => 5,
        'reason' => 'Accounts should not apply discount by default.',
    ], $accounts);
    assertBilling(($accountsDiscount['success'] ?? false) === false, 'Accounts applied a discount without permission.');

    $discount = requireBillingSuccess($billingService->applyBillingDiscount([
        'visit_id' => $visitId,
        'invoice_id' => (int)$invoiceAfterCancel['id'],
        'patient_charge_id' => $manualChargeId,
        'discount_percent' => 10,
        'reason' => 'Management-approved line regression discount.',
    ], $admin), 'Apply charge-specific billing discount');
    assertBilling(abs((float)$discount['discount_amount'] - 200.0) < 0.01, 'Charge-specific discount amount is incorrect.');
    $invoiceAfterDiscount = $billingService->getInvoiceByVisit($visitId, $accounts);
    assertBilling(abs((float)$invoiceAfterDiscount['total_amount'] - 3800.0) < 0.01, 'Invoice total did not include charge-specific discount.');
    assertBilling(abs((float)$invoiceAfterDiscount['balance_due'] - 3800.0) < 0.01, 'Invoice balance did not include charge-specific discount.');
    $balanceAfterLineDiscount = $billingService->getEncounterBalance($visitId, $accounts);
    assertBilling(abs((float)$balanceAfterLineDiscount['charge_discounts'] - 200.0) < 0.01, 'Charge discount total is incorrect.');
    assertBilling(abs((float)$balanceAfterLineDiscount['invoice_discounts']) < 0.01, 'Invoice discount total should be zero after line discount only.');
    assertBilling(abs((float)$balanceAfterLineDiscount['discounted_subtotal'] - 3800.0) < 0.01, 'Discounted subtotal is incorrect.');
    $discountRows = $billingService->listDiscountsByVisit($visitId, $admin);
    assertBilling(count($discountRows) === 1, 'Discount history did not include applied discount.');
    assertBilling((string)$discountRows[0]['discount_scope'] === 'Charge', 'Discount history did not mark charge-specific discount.');

    requireBillingSuccess(
        $billingService->cancelBillingDiscount((int)$discount['billing_discount_id'], 'Regression cancel verification.', $admin),
        'Cancel billing discount'
    );
    $invoiceAfterDiscountCancel = $billingService->getInvoiceByVisit($visitId, $accounts);
    assertBilling(abs((float)$invoiceAfterDiscountCancel['total_amount'] - 4000.0) < 0.01, 'Invoice total did not restore after discount cancellation.');

    $discount = requireBillingSuccess($billingService->applyBillingDiscount([
        'visit_id' => $visitId,
        'invoice_id' => (int)$invoiceAfterDiscountCancel['id'],
        'discount_percent' => 10,
        'reason' => 'Management-approved final invoice regression discount.',
    ], $admin), 'Apply invoice-level billing discount');
    $invoiceAfterDiscount = $billingService->getInvoiceByVisit($visitId, $accounts);
    assertBilling(abs((float)$invoiceAfterDiscount['total_amount'] - 3600.0) < 0.01, 'Invoice total did not include reapplied discount.');
    $balanceAfterInvoiceDiscount = $billingService->getEncounterBalance($visitId, $accounts);
    assertBilling(abs((float)$balanceAfterInvoiceDiscount['charge_discounts']) < 0.01, 'Charge discount total should be zero after cancelled line discount.');
    assertBilling(abs((float)$balanceAfterInvoiceDiscount['invoice_discounts'] - 400.0) < 0.01, 'Invoice discount total is incorrect.');

    $paymentOne = requireBillingSuccess($billingService->recordPayment([
        'invoice_id' => (int)$invoiceAfterDiscount['id'],
        'amount' => 1000,
        'payment_method' => 'Cash',
        'reference' => 'RCPT-001',
        'notes' => 'Partial payment.',
    ], $accounts), 'Partial payment');
    $invoiceAfterPartial = $billingService->getInvoiceByVisit($visitId, $accounts);
    assertBilling((string)$invoiceAfterPartial['status'] === 'Partially Paid', 'Invoice status should be Partially Paid.');
    assertBilling(abs((float)$invoiceAfterPartial['amount_paid'] - 1000.0) < 0.01, 'Partial payment not reflected.');

    $paymentTwo = requireBillingSuccess($billingService->recordPayment([
        'invoice_id' => (int)$invoiceAfterPartial['id'],
        'amount' => 1000,
        'payment_method' => 'Transfer',
        'reference' => 'RCPT-002',
        'notes' => 'Second partial payment.',
    ], $accounts), 'Full payment');
    $paymentId = (int)$paymentTwo['payment_id'];
    $invoiceAfterFull = $billingService->getInvoiceByVisit($visitId, $accounts);
    assertBilling((string)$invoiceAfterFull['status'] === 'Partially Paid', 'Invoice status should remain Partially Paid.');
    assertBilling(abs((float)$invoiceAfterFull['balance_due'] - 1600.0) < 0.01, 'Invoice should have remaining balance.');

    $paymentThree = requireBillingSuccess($billingService->recordPayment([
        'invoice_id' => (int)$invoiceAfterFull['id'],
        'amount' => 1600,
        'payment_method' => 'Transfer',
        'reference' => 'RCPT-003',
        'notes' => 'Final settlement.',
    ], $accounts), 'Final payment');
    $paymentId = (int)$paymentThree['payment_id'];
    $invoiceAfterFull = $billingService->getInvoiceByVisit($visitId, $accounts);
    assertBilling((string)$invoiceAfterFull['status'] === 'Paid', 'Invoice status should be Paid.');
    assertBilling(abs((float)$invoiceAfterFull['balance_due']) < 0.01, 'Invoice should be settled.');

    $receiptData = $billingService->getReceiptData($paymentId, $accounts);
    assertBilling($receiptData !== null, 'Receipt data not found.');
    assertBilling((string)$receiptData['invoice_number'] === (string)$invoiceAfterFull['invoice_number'], 'Receipt invoice number mismatch.');

    $laboratoryService = new LaboratoryService($pdo, null, null, $permissionService);
    $labGateVisitId = createBillingEncounter($pdo, $doctor, $patientId, $doctorDepartmentId, 'LAB-GATE');
    $labCreate = requireBillingSuccess($laboratoryService->createRequest([
        'visit_id' => $labGateVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'tests_requested' => 'Full Blood Count',
        'clinical_information' => 'Billing gate regression.',
        'priority' => 'Routine',
        'suggested_billable_item_id' => $laboratoryItemId,
    ], $doctor), 'Create laboratory request with automatic billing task');
    $labRequestId = (int)$labCreate['laboratory_request_id'];
    $labBillingRows = $billingService->listBillingRequests([
        'visit_id' => $labGateVisitId,
        'source_module' => 'Laboratory',
        'source_record_id' => $labRequestId,
    ], $accounts);
    assertBilling(count($labBillingRows) === 1, 'Clinical laboratory request did not create an Accounts billing task.');
    assertBilling((string)$labBillingRows[0]['status'] === 'Charged', 'Clinical laboratory billing task should be automatically charged.');
    assertBilling((int)($labBillingRows[0]['patient_charge_id'] ?? 0) > 0, 'Clinical laboratory billing task is missing automatic patient charge.');

    $blockedPending = $laboratoryService->startRequest($labRequestId, $laboratory);
    assertBilling(($blockedPending['success'] ?? true) === false, 'Laboratory started work before patient payment.');

    $labCharge = $billingService->chargeBillingRequest([
        'billing_request_id' => (int)$labBillingRows[0]['id'],
        'billable_item_id' => $laboratoryItemId,
        'quantity' => 1,
        'description' => 'Full Blood Count from clinical request.',
    ], $accounts);
    assertBilling(($labCharge['success'] ?? false) === false, 'Automatically charged laboratory request should not be charged manually.');
    $labInvoice = requireBillingSuccess($billingService->createInvoice($labGateVisitId, $accounts), 'Create laboratory gate invoice');

    $blockedUnpaid = $laboratoryService->startRequest($labRequestId, $laboratory);
    assertBilling(($blockedUnpaid['success'] ?? true) === false, 'Laboratory started work before patient payment.');

    requireBillingSuccess($billingService->recordPayment([
        'invoice_id' => (int)$labInvoice['invoice_id'],
        'amount' => 1200,
        'payment_method' => 'Cash',
        'reference' => 'RCPT-LAB-GATE',
    ], $accounts), 'Pay laboratory gate invoice');
    $allowedPaid = requireBillingSuccess($laboratoryService->startRequest($labRequestId, $laboratory), 'Laboratory starts after billing clearance');
    assertBilling(($allowedPaid['success'] ?? false) === true, 'Laboratory did not start after Accounts clearance.');

    $multiItemVisitId = createBillingEncounter($pdo, $doctor, $patientId, $doctorDepartmentId, 'LAB-MULTI');
    $multiLabCreate = requireBillingSuccess($laboratoryService->createRequest([
        'visit_id' => $multiItemVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'tests_requested' => 'Full Blood Count with sample collection',
        'clinical_information' => 'Multiple billable item regression.',
        'priority' => 'Routine',
        'suggested_billable_item_ids' => [$laboratoryItemId, $laboratoryExtraItemId],
    ], $doctor), 'Create laboratory request with multiple billable items');
    $multiRequestId = (int)$multiLabCreate['laboratory_request_id'];
    $multiBillingRows = $billingService->listBillingRequests([
        'visit_id' => $multiItemVisitId,
        'source_module' => 'Laboratory',
        'source_record_id' => $multiRequestId,
    ], $accounts);
    assertBilling(count($multiBillingRows) === 1, 'Multiple-item laboratory request did not create one Accounts billing task.');
    $multiChargeCount = (int)$pdo->query("SELECT COUNT(*) FROM patient_charges WHERE visit_id = {$multiItemVisitId} AND status = 'Active'")->fetchColumn();
    assertBilling($multiChargeCount === 2, 'Multiple-item laboratory request did not create two patient charges.');
    $multiBalance = $billingService->getEncounterBalance($multiItemVisitId, $accounts);
    assertBilling(abs((float)$multiBalance['total_charges'] - 1700.0) < 0.01, 'Multiple-item laboratory request did not total both charges.');

    $superGateVisitId = createBillingEncounter($pdo, $doctor, $patientId, $doctorDepartmentId, 'LAB-SUPER');
    $superLabCreate = requireBillingSuccess($laboratoryService->createRequest([
        'visit_id' => $superGateVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'tests_requested' => 'Super Admin Override Test',
        'priority' => 'Routine',
        'suggested_billable_item_id' => $laboratoryItemId,
    ], $doctor), 'Create super admin override laboratory request');
    $superStart = requireBillingSuccess(
        $laboratoryService->startRequest((int)$superLabCreate['laboratory_request_id'], $admin),
        'Super Admin billing gate override'
    );
    assertBilling(($superStart['success'] ?? false) === true, 'Super Admin override was blocked by billing gate.');

    $emergencyDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Emergency' LIMIT 1")->fetchColumn();
    assertBilling($emergencyDepartmentId > 0, 'Emergency department is missing.');
    $emergencyVisitId = createBillingEncounter($pdo, $doctor, $patientId, $emergencyDepartmentId, 'LAB-ER');
    $pdo->prepare("UPDATE visits SET visit_type = 'Emergency', visit_status = 'Emergency', current_department_id = :department_id WHERE id = :id")
        ->execute([':department_id' => $emergencyDepartmentId, ':id' => $emergencyVisitId]);
    $emergencyLabCreate = requireBillingSuccess($laboratoryService->createRequest([
        'visit_id' => $emergencyVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'tests_requested' => 'Emergency Full Blood Count',
        'priority' => 'Urgent',
        'suggested_billable_item_id' => $laboratoryItemId,
    ], $doctor), 'Create emergency laboratory request');
    $emergencyStart = requireBillingSuccess(
        $laboratoryService->startRequest((int)$emergencyLabCreate['laboratory_request_id'], $laboratory),
        'Emergency billing gate override'
    );
    assertBilling(($emergencyStart['success'] ?? false) === true, 'Emergency request was blocked by billing gate.');

    $overBalance = $billingService->recordPayment([
        'invoice_id' => (int)$invoiceAfterFull['id'],
        'amount' => 1,
        'payment_method' => 'Cash',
        'reference' => 'RCPT-OVER',
        'notes' => 'Should fail.',
    ], $accounts);
    assertBilling(($overBalance['success'] ?? false) === false, 'Over-balance payment was accepted.');

    $adminCharge = requireBillingSuccess($billingService->createCharge([
        'visit_id' => $visitId2,
        'billable_item_id' => $consultationItemId,
        'quantity' => 1,
        'description' => 'Admin override charge.',
        'source_module' => 'Billing',
    ], $admin), 'Administrator charge');
    assertBilling(($adminCharge['success'] ?? false) === true, 'Administrator override failed.');
    requireBillingSuccess($billingService->cancelCharge((int)$adminCharge['patient_charge_id'], $admin), 'Administrator cancel charge');

    assertBilling(($billingService->createCharge([
        'visit_id' => $visitId,
        'billable_item_id' => $consultationItemId,
        'quantity' => 1,
        'description' => 'Doctor should be blocked.',
        'source_module' => 'Billing',
    ], $doctor)['success'] ?? false) === false, 'Doctor should not create patient charges.');
    assertBilling(($billingService->recordPayment([
        'invoice_id' => (int)$invoiceAfterFull['id'],
        'amount' => 1,
        'payment_method' => 'Cash',
        'reference' => 'RCPT-DOCTOR',
    ], $doctor)['success'] ?? false) === false, 'Doctor should not record payments.');
    assertBilling(($billingService->createCharge([
        'visit_id' => $visitId,
        'billable_item_id' => $consultationItemId,
        'quantity' => 1,
        'description' => 'Nurse should be blocked.',
        'source_module' => 'Billing',
    ], $nurse)['success'] ?? false) === false, 'Nurse should not create patient charges.');

    assertBilling(str_contains(file_get_contents(__DIR__ . '/../layouts/sidebar.php'), '/modules/billing/index.php'), 'Billing sidebar entry missing.');
    assertBilling(str_contains(file_get_contents(__DIR__ . '/../modules/visits/partials/tabs/billing.php'), 'Billing / Charges') || str_contains(file_get_contents(__DIR__ . '/../modules/visits/partials/tabs/billing.php'), '_summary.php'), 'Workspace billing tab not wired.');

    $charges = $billingService->listChargesByVisit($visitId, $accounts);
    assertBilling(count($charges) === 3, 'Unexpected billing charge count.');

    $payments = $billingService->listPayments($visitId, $accounts);
    assertBilling(count($payments) === 3, 'Unexpected payment count.');

    $auditCount = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE visit_id = {$visitId}")->fetchColumn();
    assertBilling($auditCount >= 4, 'Billing audit entries were not written.');

    $receiptGuard = $billingService->getReceiptData($paymentId, $doctor);
    assertBilling($receiptGuard === null, 'Doctor should not be able to view receipts.');

    $completedVisit = createBillingEncounter($pdo, $admin, $patientId2, $doctorDepartmentId, '003');
    $pdo->prepare("UPDATE visits SET visit_status = 'Completed' WHERE id = :id")->execute([':id' => $completedVisit]);
    $completedRequest = $billingService->createBillingRequest([
        'visit_id' => $completedVisit,
        'department_id' => $doctorDepartmentId,
        'description' => 'Should fail on completed encounter.',
        'quantity' => 1,
    ], $doctor);
    assertBilling(($completedRequest['success'] ?? false) === false, 'Completed encounter accepted a billing request.');
    $completedCharge = $billingService->createCharge([
        'visit_id' => $completedVisit,
        'billable_item_id' => $consultationItemId,
        'quantity' => 1,
        'description' => 'Should fail on completed encounter.',
        'source_module' => 'Billing',
    ], $accounts);
    assertBilling(($completedCharge['success'] ?? false) === false, 'Completed encounter accepted a new charge.');

    $cancelledVisit = createBillingEncounter($pdo, $admin, $patientId2, $doctorDepartmentId, '004');
    $cancelledInvoice = requireBillingSuccess($billingService->createInvoice($cancelledVisit, $accounts), 'Create invoice for cancelled encounter');
    $pdo->prepare("UPDATE visits SET visit_status = 'Cancelled' WHERE id = :id")->execute([':id' => $cancelledVisit]);
    $cancelledPayment = $billingService->recordPayment([
        'invoice_id' => (int)$cancelledInvoice['invoice_id'],
        'amount' => 1,
        'payment_method' => 'Cash',
        'reference' => 'RCPT-CANCELLED',
    ], $accounts);
    assertBilling(($cancelledPayment['success'] ?? false) === false, 'Cancelled encounter accepted a payment.');

    $pdo->exec("DELETE FROM billing_requests WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
    $pdo->exec("DELETE FROM billing_discounts WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
    $pdo->exec("DELETE FROM payments WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
    $pdo->exec("DELETE FROM invoices WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
    $pdo->exec("DELETE FROM patient_charges WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
    $pdo->exec("DELETE lr FROM laboratory_results lr INNER JOIN laboratory_requests lq ON lq.id = lr.laboratory_request_id INNER JOIN visits v ON v.id = lq.visit_id WHERE v.visit_number LIKE 'BIL-%'");
    $pdo->exec("DELETE lq FROM laboratory_requests lq INNER JOIN visits v ON v.id = lq.visit_id WHERE v.visit_number LIKE 'BIL-%'");
    $pdo->exec("DELETE FROM encounter_events WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
    $pdo->exec("DELETE FROM audit_logs WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'BIL-%')");
    $pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'BIL-%'");
    $pdo->exec("DELETE FROM billable_items WHERE item_code LIKE 'BIL-%'");

    echo "PASS: Phase 4.4 Billing regression passed." . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    throw $exception;
}
