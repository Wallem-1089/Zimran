<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/AdmissionService.php';
require_once __DIR__ . '/../services/PermissionService.php';

function assertAdmission(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireAdmissionSuccess(array $result, string $label): array
{
    assertAdmission(($result['success'] ?? false) === true, $label . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertAdmission(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Inpatient admission tests are not isolated from the live database.'
);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL .
    'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/037_inpatient_admissions_up.sql', 37);
$manager->apply(__DIR__ . '/../database/migrations/061_admission_bed_permission_repair_up.sql', 61);

$permissionService = new PermissionService($pdo);
$service = new AdmissionService($pdo, null, null, $permissionService);

$users = [];
$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('admin','dev_doctor','dev_nurse','dev_records','dev_reception')
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}
foreach (['admin','dev_doctor','dev_nurse','dev_records','dev_reception'] as $username) {
    assertAdmission(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['admin'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];
$records = $users['dev_records'];
$reception = $users['dev_reception'];
$nursingDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Nursing' LIMIT 1")->fetchColumn();
assertAdmission($nursingDepartmentId > 0, 'Nursing department is missing.');

try {
    $pdo->exec("DELETE FROM admission_movements WHERE patient_id IN (SELECT id FROM patients WHERE hospital_number LIKE 'ADM-%')");
    $pdo->exec("DELETE FROM admissions WHERE patient_id IN (SELECT id FROM patients WHERE hospital_number LIKE 'ADM-%')");
    $pdo->exec("UPDATE ward_beds SET bed_status = 'Available' WHERE bed_label LIKE 'ADM-%'");
    $pdo->exec("DELETE FROM ward_beds WHERE bed_label LIKE 'ADM-%'");
    $pdo->exec("DELETE FROM wards WHERE ward_code LIKE 'ADM-%'");
    $pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'ADM-VIS-%'");
    $pdo->exec("DELETE FROM patients WHERE hospital_number LIKE 'ADM-%'");
    $pdo->exec("DELETE FROM audit_logs WHERE module = 'Admissions'");
    $pdo->exec("DELETE FROM encounter_events WHERE event_type IN ('PATIENT_ADMITTED','ADMISSION_TRANSFERRED','PATIENT_DISCHARGED_FROM_WARD','ADMISSION_CANCELLED')");

    foreach (['view_admissions','create_admission','transfer_admission','discharge_admission','manage_wards_beds'] as $permission) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key AND is_active = 1');
        $stmt->execute([':permission_key' => $permission]);
        assertAdmission((int)$stmt->fetchColumn() === 1, 'Missing permission ' . $permission . '.');
    }

    assertAdmission($permissionService->canViewAdmissions($nurse), 'Nurse should view admissions.');
    assertAdmission($permissionService->canCreateAdmission(['visit_status' => 'Nursing'], $doctor), 'Doctor should create admission.');
    assertAdmission($permissionService->canTransferAdmission(['visit_status' => 'Nursing'], $nurse), 'Nurse should transfer admission.');
    assertAdmission($permissionService->canTransferAdmission(['visit_status' => 'Nursing'], $doctor), 'Doctor should transfer/change admission bed.');
    assertAdmission($permissionService->canTransferAdmission(['visit_status' => 'Nursing'], $reception), 'Receptionist should transfer/change admission bed.');
    assertAdmission($permissionService->canCreateAdmission(['visit_status' => 'Nursing'], [
        'id' => (int)$admin['id'],
        'role_name' => 'System Administrator',
        'department_name' => 'Administrator',
    ]), 'System Administrator should create admissions.');
    assertAdmission($permissionService->canTransferAdmission(['visit_status' => 'Nursing'], [
        'id' => (int)$admin['id'],
        'role_name' => 'System Administrator',
        'department_name' => 'Administrator',
    ]), 'System Administrator should transfer/change admission bed.');
    assertAdmission(!$permissionService->canTransferAdmission(['visit_status' => 'Completed'], $nurse), 'Completed encounter should block admission transfer.');

    $ward = requireAdmissionSuccess($service->createWard([
        'ward_name' => 'ADM Test Ward',
        'ward_code' => 'ADM-WARD',
        'department_id' => $nursingDepartmentId,
        'description' => 'Test ward.',
    ], $records), 'Create ward');
    $wardId = (int)$ward['ward_id'];

    $bedOne = requireAdmissionSuccess($service->addBed([
        'ward_id' => $wardId,
        'bed_label' => 'ADM-BED-1',
    ], $nurse), 'Create first bed');
    $bedOneId = (int)$bedOne['bed_id'];

    $bedTwo = requireAdmissionSuccess($service->addBed([
        'ward_id' => $wardId,
        'bed_label' => 'ADM-BED-2',
    ], $nurse), 'Create second bed');
    $bedTwoId = (int)$bedTwo['bed_id'];

    $pdo->prepare("
        INSERT INTO patients (
            hospital_number, first_name, last_name, gender, date_of_birth,
            registered_by, created_at, updated_at
        ) VALUES (
            'ADM-001', 'Admission', 'Patient', 'Male', '1990-01-01',
            :registered_by, NOW(), NOW()
        )
    ")->execute([':registered_by' => (int)$admin['id']]);
    $patientId = (int)$pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO visits (
            visit_number, patient_id, visit_date, visit_type,
            current_department_id, current_department_received_status,
            current_department_received_at, current_department_received_by,
            visit_status, created_by, created_at, updated_at
        ) VALUES (
            'ADM-VIS-001', :patient_id, NOW(), 'Inpatient',
            :department_id, 'Received', NOW(), :received_by,
            'Nursing', :created_by, NOW(), NOW()
        )
    ")->execute([
        ':patient_id' => $patientId,
        ':department_id' => $nursingDepartmentId,
        ':received_by' => (int)$nurse['id'],
        ':created_by' => (int)$admin['id'],
    ]);
    $visitId = (int)$pdo->lastInsertId();

    $admitted = requireAdmissionSuccess($service->admit([
        'visit_id' => $visitId,
        'patient_id' => $patientId,
        'ward_id' => $wardId,
        'bed_id' => $bedOneId,
        'admission_type' => 'Emergency',
        'admission_diagnosis' => 'Test admission diagnosis.',
        'admission_notes' => 'Test admission notes.',
    ], $doctor), 'Admit patient');
    $admissionId = (int)$admitted['admission_id'];

    $bedStatus = (string)$pdo->query("SELECT bed_status FROM ward_beds WHERE id = {$bedOneId}")->fetchColumn();
    assertAdmission($bedStatus === 'Occupied', 'Admitting patient did not occupy the bed.');

    $duplicate = $service->admit([
        'visit_id' => $visitId,
        'patient_id' => $patientId,
        'ward_id' => $wardId,
        'bed_id' => $bedTwoId,
    ], $doctor);
    assertAdmission(($duplicate['success'] ?? true) === false, 'Duplicate admission was allowed.');

    $pdo->prepare("
        INSERT INTO patients (
            hospital_number, first_name, last_name, gender, registered_by, created_at, updated_at
        ) VALUES ('ADM-002', 'Occupied', 'Bed', 'Female', :registered_by, NOW(), NOW())
    ")->execute([':registered_by' => (int)$admin['id']]);
    $secondPatientId = (int)$pdo->lastInsertId();
    $pdo->prepare("
        INSERT INTO visits (
            visit_number, patient_id, visit_date, visit_type,
            current_department_id, current_department_received_status,
            visit_status, created_by, created_at, updated_at
        ) VALUES ('ADM-VIS-002', :patient_id, NOW(), 'Inpatient', :department_id, 'Received', 'Nursing', :created_by, NOW(), NOW())
    ")->execute([
        ':patient_id' => $secondPatientId,
        ':department_id' => $nursingDepartmentId,
        ':created_by' => (int)$admin['id'],
    ]);
    $secondVisitId = (int)$pdo->lastInsertId();
    $occupied = $service->admit([
        'visit_id' => $secondVisitId,
        'patient_id' => $secondPatientId,
        'ward_id' => $wardId,
        'bed_id' => $bedOneId,
    ], $doctor);
    assertAdmission(($occupied['success'] ?? true) === false, 'Occupied bed was accepted.');

    requireAdmissionSuccess($service->transfer($admissionId, [
        'ward_id' => $wardId,
        'bed_id' => $bedTwoId,
        'reason' => 'Test transfer.',
    ], $nurse), 'Transfer admission');
    assertAdmission((string)$pdo->query("SELECT bed_status FROM ward_beds WHERE id = {$bedOneId}")->fetchColumn() === 'Available', 'Transfer did not release previous bed.');
    assertAdmission((string)$pdo->query("SELECT bed_status FROM ward_beds WHERE id = {$bedTwoId}")->fetchColumn() === 'Occupied', 'Transfer did not occupy new bed.');

    requireAdmissionSuccess($service->discharge($admissionId, [
        'discharge_destination' => 'Home',
        'discharge_notes' => 'Stable for discharge.',
    ], $nurse), 'Discharge admission');
    assertAdmission((string)$pdo->query("SELECT bed_status FROM ward_beds WHERE id = {$bedTwoId}")->fetchColumn() === 'Available', 'Discharge did not release bed.');

    $closedTransfer = $service->transfer($admissionId, [
        'ward_id' => $wardId,
        'bed_id' => $bedOneId,
    ], $nurse);
    assertAdmission(($closedTransfer['success'] ?? true) === false, 'Closed admission transfer was allowed.');

    $movementCount = (int)$pdo->query("SELECT COUNT(*) FROM admission_movements WHERE admission_id = {$admissionId}")->fetchColumn();
    assertAdmission($movementCount >= 3, 'Admission movement history is incomplete.');
    $auditCount = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE module = 'Admissions'")->fetchColumn();
    assertAdmission($auditCount >= 3, 'Admission audit logs were not written.');
    $eventCount = (int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE visit_id = {$visitId} AND event_type IN ('PATIENT_ADMITTED','ADMISSION_TRANSFERRED','PATIENT_DISCHARGED_FROM_WARD')")->fetchColumn();
    assertAdmission($eventCount >= 3, 'Admission encounter events were not written.');

    $workspace = (string)file_get_contents(__DIR__ . '/../modules/visits/partials/workspace_navigation.php');
    assertAdmission(str_contains($workspace, 'tab=admission'), 'Workspace Admission tab is missing.');
    $sidebar = (string)file_get_contents(__DIR__ . '/../layouts/sidebar.php');
    assertAdmission(str_contains($sidebar, '/modules/admissions/index.php'), 'Sidebar Admissions destination is missing.');
} finally {
    $pdo->exec("DELETE FROM admission_movements WHERE patient_id IN (SELECT id FROM patients WHERE hospital_number LIKE 'ADM-%')");
    $pdo->exec("DELETE FROM admissions WHERE patient_id IN (SELECT id FROM patients WHERE hospital_number LIKE 'ADM-%')");
    $pdo->exec("DELETE FROM encounter_events WHERE visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'ADM-VIS-%')");
    $pdo->exec("DELETE FROM audit_logs WHERE patient_id IN (SELECT id FROM patients WHERE hospital_number LIKE 'ADM-%') OR visit_id IN (SELECT id FROM visits WHERE visit_number LIKE 'ADM-VIS-%')");
    $pdo->exec("UPDATE ward_beds SET bed_status = 'Available' WHERE bed_label LIKE 'ADM-%'");
    $pdo->exec("DELETE FROM ward_beds WHERE bed_label LIKE 'ADM-%'");
    $pdo->exec("DELETE FROM wards WHERE ward_code LIKE 'ADM-%'");
    $pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'ADM-VIS-%'");
    $pdo->exec("DELETE FROM patients WHERE hospital_number LIKE 'ADM-%'");
}

fwrite(STDOUT, 'PASS: Inpatient Admissions regression passed.' . PHP_EOL);
