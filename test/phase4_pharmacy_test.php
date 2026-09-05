<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/AccountsService.php';
require_once __DIR__ . '/../services/PharmacyService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/StoreService.php';
require_once __DIR__ . '/../services/VisitService.php';

function assertPharmacy(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requirePharmacySuccess(array $result, string $label): array
{
    assertPharmacy(($result['success'] ?? false) === true, $label . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

function createPharmacyEncounter(PDO $pdo, array $actor, int $patientId, int $departmentId, string $status, string $suffix): int
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
        ':visit_number' => 'P43-' . $status . '-' . $suffix,
        ':patient_id' => $patientId,
        ':visit_type' => 'Outpatient',
        ':department_id' => $departmentId,
        ':attending_doctor_id' => (int)($actor['id'] ?? 0),
        ':received_status' => 'Received',
        ':visit_status' => $status,
        ':created_by' => (int)($actor['id'] ?? 0),
    ]);

    return (int)$pdo->lastInsertId();
}

function routePharmacyEncounter(PDO $pdo, int $visitId, int $departmentId): void
{
    $stmt = $pdo->prepare("
        UPDATE visits
        SET current_department_id = :department_id,
            current_department_received_status = 'Received',
            visit_status = 'Pharmacy',
            updated_at = NOW()
        WHERE id = :visit_id
    ");
    $stmt->execute([
        ':department_id' => $departmentId,
        ':visit_id' => $visitId,
    ]);
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertPharmacy(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Phase 4.3 tests are not isolated from the live database.'
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

$pdo->exec("DELETE FROM encounter_events WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'P43-%')");
$pdo->exec("DELETE FROM audit_logs WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'P43-%')");
$pdo->exec("DELETE FROM pharmacy_dispensing WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'P43-%')");
$pdo->exec("DELETE FROM prescriptions WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'P43-%')");
$pdo->exec("DELETE FROM stock_transactions WHERE inventory_item_id IN (SELECT id FROM inventory_items WHERE item_code LIKE 'P43-%')");
$pdo->exec("DELETE FROM department_stock_balances WHERE inventory_item_id IN (SELECT id FROM inventory_items WHERE item_code LIKE 'P43-%')");
$pdo->exec("DELETE FROM inventory_items WHERE item_code LIKE 'P43-%'");
$pdo->exec("DELETE FROM billable_items WHERE item_code LIKE 'P43-%'");
$pdo->exec("DELETE FROM user_departments WHERE user_id IN (SELECT id FROM users WHERE username = 'dev_pharmacy')");
$pdo->exec("DELETE FROM users WHERE username = 'dev_pharmacy'");
$pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'P43-%'");

$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('walter','dev_doctor','dev_nurse','dev_records','dev_pharmacy','dev_accounts')
")->fetchAll(PDO::FETCH_ASSOC);
$users = [];
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}

foreach (['walter', 'dev_doctor', 'dev_nurse', 'dev_records', 'dev_accounts'] as $username) {
    assertPharmacy(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['walter'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];
$records = $users['dev_records'];
$accounts = $users['dev_accounts'];

$pharmacyRoleId = (int)$pdo->query("SELECT id FROM roles WHERE role_name = 'Pharmacist' LIMIT 1")->fetchColumn();
$pharmacyDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Pharmacy' LIMIT 1")->fetchColumn();
$doctorDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Doctor' LIMIT 1")->fetchColumn();
assertPharmacy($pharmacyRoleId > 0 && $pharmacyDepartmentId > 0 && $doctorDepartmentId > 0, 'Pharmacy, Doctor role or department is missing.');

if (!isset($users['dev_pharmacy'])) {
    $pdo->prepare('
        INSERT INTO users (
            employee_id, first_name, last_name, gender, phone, email, username, password,
            department_id, role_id, status, created_at
        ) VALUES (
            :employee_id, :first_name, :last_name, :gender, :phone, :email, :username, :password,
            :department_id, :role_id, \'Active\', NOW()
        )
    ')->execute([
        ':employee_id' => 'DEV-PHA-001',
        ':first_name' => 'Dev',
        ':last_name' => 'Pharmacy',
        ':gender' => null,
        ':phone' => null,
        ':email' => 'dev.pharmacy@example.com',
        ':username' => 'dev_pharmacy',
        ':password' => password_hash('pharmacy1234', PASSWORD_DEFAULT),
        ':department_id' => $pharmacyDepartmentId,
        ':role_id' => $pharmacyRoleId,
    ]);
    $pdo->prepare('
        INSERT INTO user_departments (user_id, department_id, is_primary, is_active, assigned_by)
        SELECT id, department_id, 1, 1, 1
        FROM users
        WHERE username = :username
    ')->execute([':username' => 'dev_pharmacy']);
    $users['dev_pharmacy'] = $pdo->query("
        SELECT u.*, r.role_name, d.department_name
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        INNER JOIN departments d ON d.id = u.department_id
        WHERE u.username = 'dev_pharmacy'
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
}

$pharmacist = $users['dev_pharmacy'];

$patientRow = $pdo->query("
    SELECT id, hospital_number
    FROM patients
    WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002')
    ORDER BY hospital_number
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
assertPharmacy((bool)$patientRow, 'Patient fixture is missing.');
$patientId = (int)$patientRow['id'];

$service = new PharmacyService($pdo, new StoreService($pdo, null, new PermissionService($pdo)), null, null, null, new PermissionService($pdo), new VisitService($pdo));
$storeService = new StoreService($pdo, null, new PermissionService($pdo));
$accountsService = new AccountsService($pdo, null, new PermissionService($pdo));
$permissionService = new PermissionService($pdo);

try {
    foreach (['view_pharmacy', 'create_prescription', 'edit_prescription', 'dispense_prescription'] as $permissionKey) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key AND is_active = 1');
        $stmt->execute([':permission_key' => $permissionKey]);
        assertPharmacy((int)$stmt->fetchColumn() === 1, 'Missing permission ' . $permissionKey . '.');
    }

    assertPharmacy(in_array('prescriptions', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Prescriptions table is missing.');
    assertPharmacy(in_array('pharmacy_dispensing', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Pharmacy dispensing table is missing.');
    assertPharmacy(str_contains(file_get_contents(__DIR__ . '/../layouts/sidebar.php'), '/modules/pharmacy/index.php'), 'Sidebar missing Pharmacy destination.');
    assertPharmacy(str_contains(file_get_contents(__DIR__ . '/../modules/visits/partials/workspace_navigation.php'), 'tab=pharmacy'), 'Workspace navigation missing Pharmacy tab.');
    assertPharmacy(str_contains(file_get_contents(__DIR__ . '/../modules/consultation/view.php'), 'Create Prescription'), 'Consultation view missing Pharmacy integration.');

    $doctorEncounterStub = [
        'visit_status' => 'Doctor',
        'patient_id' => $patientId,
        'current_department_id' => $doctorDepartmentId,
        'current_department_received_status' => 'Received',
        'department_name' => 'Doctor',
    ];
    $pharmacyEncounterStub = [
        'visit_status' => 'Pharmacy',
        'patient_id' => $patientId,
        'current_department_id' => $pharmacyDepartmentId,
        'current_department_received_status' => 'Received',
        'department_name' => 'Pharmacy',
    ];

    assertPharmacy($permissionService->canViewPharmacy($patientId, $pharmacist), 'Pharmacist should be able to view pharmacy records.');
    assertPharmacy($permissionService->canCreatePrescription($doctorEncounterStub, $doctor, 'Clinical'), 'Doctor should create clinical prescriptions.');
    assertPharmacy($permissionService->canCreatePrescription($pharmacyEncounterStub, $pharmacist, 'Direct'), 'Pharmacist should create direct prescriptions.');
    assertPharmacy($permissionService->canDispensePrescription($pharmacyEncounterStub, $pharmacist), 'Pharmacist should dispense prescriptions.');
    assertPharmacy(!$permissionService->canCreatePrescription(['visit_status' => 'Doctor', 'patient_id' => $patientId, 'department_name' => 'Doctor'], $nurse, 'Clinical'), 'Nurse should not create prescriptions.');

    $billable = requirePharmacySuccess($accountsService->createItem([
        'item_code' => 'P43-BILL-001',
        'item_name' => 'Amoxicillin 500 mg',
        'item_type' => 'Product',
        'department_id' => $pharmacyDepartmentId,
        'description' => 'Pharmacy test price.',
        'unit_price' => 150,
        'unit' => 'Capsule',
        'is_active' => 1,
    ], $admin), 'Create price catalogue item');
    $billableItemId = (int)$billable['billable_item_id'];

    $item = requirePharmacySuccess($storeService->createItem([
        'item_code' => 'P43-ITEM-001',
        'item_name' => 'Amoxicillin 500 mg',
        'category' => 'Medication',
        'unit' => 'Capsule',
        'description' => 'Pharmacy stock item.',
        'billable_item_id' => $billableItemId,
        'is_active' => 1,
    ], $admin), 'Create inventory item');
    $itemId = (int)$item['inventory_item_id'];

    requirePharmacySuccess($storeService->receiveStock([
        'inventory_item_id' => $itemId,
        'quantity' => 50,
        'reference' => 'P43-RCV-001',
        'remarks' => 'Initial pharmacy receipt.',
    ], $admin), 'Receive store stock');

    requirePharmacySuccess($storeService->issueStock([
        'inventory_item_id' => $itemId,
        'department_id' => $pharmacyDepartmentId,
        'quantity' => 20,
        'reference' => 'P43-ISS-001',
        'remarks' => 'Issued to Pharmacy.',
    ], $admin), 'Issue stock to pharmacy');

    $doctorVisitId = createPharmacyEncounter($pdo, $doctor, $patientId, $doctorDepartmentId, 'Doctor', 'DOC-' . time());
    $pharmacyVisitId = createPharmacyEncounter($pdo, $pharmacist, $patientId, $pharmacyDepartmentId, 'Pharmacy', 'PHA-' . time());
    $completedVisitId = createPharmacyEncounter($pdo, $doctor, $patientId, $doctorDepartmentId, 'Completed', 'CMP-' . time());

    $clinicalCreate = requirePharmacySuccess($service->createPrescription([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'prescription_source' => 'Clinical',
        'inventory_item_id' => $itemId,
        'medication_name' => 'Amoxicillin 500 mg',
        'dosage' => '1 capsule',
        'frequency' => '3 times daily',
        'duration' => '5 days',
        'quantity' => 15,
        'instructions' => 'Take after meals.',
    ], $doctor), 'Create clinical prescription');
    $clinicalPrescriptionId = (int)$clinicalCreate['prescription_id'];

    $clinicalPrescription = $service->getPrescriptionById($clinicalPrescriptionId, $doctor);
    assertPharmacy($clinicalPrescription !== null, 'Clinical prescription not returned.');
    assertPharmacy((string)$clinicalPrescription['status'] === 'Prescribed', 'Clinical prescription status is incorrect.');
    assertPharmacy((float)($clinicalPrescription['unit_price'] ?? 0) === 150.00, 'Accounts price linkage did not load.');

    $doctorWorklist = $service->listByVisit($doctorVisitId, $doctor);
    assertPharmacy(count($doctorWorklist) >= 1, 'Doctor encounter pharmacy history is empty.');
    $patientWorklist = $service->listByPatient($patientId, $doctor);
    assertPharmacy(count($patientWorklist) >= 1, 'Patient pharmacy history is empty.');
    $pharmacyWorklist = $service->listWorklist($pharmacist, ['status' => 'Prescribed']);
    assertPharmacy(count($pharmacyWorklist) >= 1, 'Pharmacy worklist did not include the clinical prescription.');

    $updated = requirePharmacySuccess($service->updatePrescription($clinicalPrescriptionId, [
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'prescription_source' => 'Clinical',
        'inventory_item_id' => $itemId,
        'medication_name' => 'Amoxicillin 500 mg Updated',
        'dosage' => '1 capsule',
        'frequency' => '3 times daily',
        'duration' => '5 days',
        'quantity' => 15,
        'instructions' => 'Take after meals.',
    ], $doctor), 'Update prescription');
    assertPharmacy((string)$service->getPrescriptionById($clinicalPrescriptionId, $doctor)['medication_name'] === 'Amoxicillin 500 mg Updated', 'Prescription update did not persist.');

    $pharmacyCreate = requirePharmacySuccess($service->createPrescription([
        'visit_id' => $pharmacyVisitId,
        'patient_id' => $patientId,
        'prescription_source' => 'Direct',
        'inventory_item_id' => $itemId,
        'medication_name' => 'Amoxicillin 500 mg',
        'dosage' => '1 capsule',
        'frequency' => '2 times daily',
        'duration' => '5 days',
        'quantity' => 5,
        'instructions' => 'External prescription.',
    ], $pharmacist), 'Create direct prescription');
    $directPrescriptionId = (int)$pharmacyCreate['prescription_id'];

    routePharmacyEncounter($pdo, $doctorVisitId, $pharmacyDepartmentId);
    $dispenseResult = requirePharmacySuccess($service->dispense($clinicalPrescriptionId, [
        'quantity_dispensed' => 15,
        'dispensing_notes' => 'Dispensed at the counter.',
    ], $pharmacist), 'Dispense clinical prescription');
    assertPharmacy(($dispenseResult['success'] ?? false) === true, 'Dispense failed.');

    $afterDispense = $service->getPrescriptionById($clinicalPrescriptionId, $doctor);
    assertPharmacy((string)$afterDispense['status'] === 'Dispensed', 'Prescription did not become dispensed.');

    $pharmacyBalance = $storeService->getDepartmentBalance($itemId, $pharmacyDepartmentId, $pharmacist);
    assertPharmacy((float)($pharmacyBalance['quantity'] ?? 0) === 5.00, 'Pharmacy stock balance is incorrect after dispensing.');

    $dupDispense = $service->dispense($clinicalPrescriptionId, [
        'quantity_dispensed' => 15,
        'dispensing_notes' => 'Duplicate dispense should fail.',
    ], $pharmacist);
    assertPharmacy(!($dupDispense['success'] ?? true), 'Duplicate dispensing was accepted.');

    $cancelResult = requirePharmacySuccess($service->cancelPrescription($directPrescriptionId, $pharmacist, 'No longer needed.'), 'Cancel direct prescription');
    assertPharmacy(($cancelResult['success'] ?? false) === true, 'Cancellation failed.');
    $cancelDispense = $service->dispense($directPrescriptionId, ['quantity_dispensed' => 5], $pharmacist);
    assertPharmacy(!($cancelDispense['success'] ?? true), 'Cancelled prescription was dispensed.');

    $mismatch = $service->createPrescription([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId + 999,
        'prescription_source' => 'Clinical',
        'inventory_item_id' => $itemId,
        'medication_name' => 'Mismatch',
        'dosage' => '1 capsule',
        'frequency' => 'once daily',
        'duration' => '3 days',
        'quantity' => 1,
        'instructions' => 'Should fail.',
    ], $doctor);
    assertPharmacy(!($mismatch['success'] ?? true), 'Patient/visit mismatch was accepted.');

    $closedMutation = $service->createPrescription([
        'visit_id' => $completedVisitId,
        'patient_id' => $patientId,
        'prescription_source' => 'Clinical',
        'inventory_item_id' => $itemId,
        'medication_name' => 'Closed',
        'dosage' => '1',
        'frequency' => 'once',
        'duration' => '1 day',
        'quantity' => 1,
        'instructions' => 'Should fail.',
    ], $doctor);
    assertPharmacy(!($closedMutation['success'] ?? true), 'Completed encounter accepted prescription creation.');

    $inactiveItem = requirePharmacySuccess($storeService->deactivateItem($itemId, $admin), 'Deactivate inventory item');
    assertPharmacy(($inactiveItem['success'] ?? false) === true, 'Inventory deactivate failed.');
    $inactivePrescription = $service->createPrescription([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'prescription_source' => 'Clinical',
        'inventory_item_id' => $itemId,
        'medication_name' => 'Amoxicillin 500 mg',
        'dosage' => '1 capsule',
        'frequency' => 'once daily',
        'duration' => '3 days',
        'quantity' => 1,
        'instructions' => 'Inactive item should fail.',
    ], $doctor);
    assertPharmacy(!($inactivePrescription['success'] ?? true), 'Inactive inventory item was accepted.');
    requirePharmacySuccess($storeService->activateItem($itemId, $admin), 'Reactivate inventory item');

    $doctorDenied = $service->createPrescription([
        'visit_id' => $pharmacyVisitId,
        'patient_id' => $patientId,
        'prescription_source' => 'Direct',
        'inventory_item_id' => $itemId,
        'medication_name' => 'Direct by doctor',
        'dosage' => '1 capsule',
        'frequency' => 'once daily',
        'duration' => '1 day',
        'quantity' => 1,
        'instructions' => 'Should fail.',
    ], $doctor);
    assertPharmacy(!($doctorDenied['success'] ?? true), 'Doctor created a direct prescription unexpectedly.');

    $nurseDenied = $service->updatePrescription($clinicalPrescriptionId, [
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'prescription_source' => 'Clinical',
        'inventory_item_id' => $itemId,
        'medication_name' => 'Nurse edit should fail',
        'dosage' => '1 capsule',
        'frequency' => '3 times daily',
        'duration' => '5 days',
        'quantity' => 15,
        'instructions' => 'No.',
    ], $nurse);
    assertPharmacy(!($nurseDenied['success'] ?? true), 'Nurse edited a prescription unexpectedly.');

    $adminCreate = requirePharmacySuccess($service->createPrescription([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'prescription_source' => 'Clinical',
        'inventory_item_id' => $itemId,
        'medication_name' => 'Admin clinical record',
        'dosage' => '1 capsule',
        'frequency' => 'once daily',
        'duration' => '2 days',
        'quantity' => 2,
        'instructions' => 'Admin test.',
    ], $admin), 'Administrator create prescription');
    assertPharmacy((int)$pdo->query("SELECT COUNT(*) FROM prescriptions WHERE visit_id = $doctorVisitId AND patient_id = $patientId AND status = 'Dispensed'")->fetchColumn() >= 1, 'Dispense record missing.');

    assertPharmacy((int)$pdo->query("
        SELECT COUNT(*)
        FROM audit_logs
        WHERE module = 'Pharmacy'
          AND action IN ('PRESCRIPTION_CREATED', 'PRESCRIPTION_UPDATED', 'PRESCRIPTION_CANCELLED', 'PRESCRIPTION_DISPENSED')
    ")->fetchColumn() >= 4, 'Pharmacy audit trail is incomplete.');

    assertPharmacy((int)$pdo->query("
        SELECT COUNT(*)
        FROM encounter_events
        WHERE visit_id IN ($doctorVisitId, $pharmacyVisitId)
          AND event_type IN ('PRESCRIPTION_CREATED', 'PRESCRIPTION_DISPENSED')
    ")->fetchColumn() >= 2, 'Pharmacy encounter events are incomplete.');

    echo 'PASS: Phase 4.3 Pharmacy regression passed.' . PHP_EOL;
} finally {
    $pdo->exec("DELETE FROM encounter_events WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'P43-%')");
    $pdo->exec("DELETE FROM audit_logs WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'P43-%')");
    $pdo->exec("DELETE FROM pharmacy_dispensing WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'P43-%')");
    $pdo->exec("DELETE FROM prescriptions WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'P43-%')");
    $pdo->exec("DELETE FROM stock_transactions WHERE inventory_item_id IN (SELECT id FROM inventory_items WHERE item_code LIKE 'P43-%')");
    $pdo->exec("DELETE FROM department_stock_balances WHERE inventory_item_id IN (SELECT id FROM inventory_items WHERE item_code LIKE 'P43-%')");
    $pdo->exec("DELETE FROM inventory_items WHERE item_code LIKE 'P43-%'");
    $pdo->exec("DELETE FROM billable_items WHERE item_code LIKE 'P43-%'");
    $pdo->exec("DELETE FROM user_departments WHERE user_id IN (SELECT id FROM users WHERE username = 'dev_pharmacy')");
    $pdo->exec("DELETE FROM users WHERE username = 'dev_pharmacy'");
    $pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'P43-%'");
}
