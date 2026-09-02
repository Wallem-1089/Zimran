<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/TheatreService.php';

function assertTheatre(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireTheatreSuccess(array $result, string $label): array
{
    assertTheatre(($result['success'] ?? false) === true, $label . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

function createTheatreEncounter(PDO $pdo, array $actor, int $patientId, int $departmentId, string $status, string $suffix): int
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
        ':visit_number' => 'P37-' . $status . '-' . $suffix,
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

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertTheatre(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Phase 3.7 tests are not isolated from the live database.'
);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL .
    'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/029_phase3_theatre_up.sql', 29);

$pdo->exec("DELETE ce FROM encounter_events ce INNER JOIN visits v ON v.id = ce.visit_id WHERE v.visit_number LIKE 'P37-%'");
$pdo->exec("DELETE al FROM audit_logs al INNER JOIN visits v ON v.id = al.visit_id WHERE v.visit_number LIKE 'P37-%'");
$pdo->exec("DELETE tr FROM theatre_records tr INNER JOIN visits v ON v.id = tr.visit_id WHERE v.visit_number LIKE 'P37-%'");
$pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'P37-%'");
$pdo->exec("DELETE FROM users WHERE username = 'dev_theatre'");

$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('walter','dev_doctor','dev_nurse')
")->fetchAll(PDO::FETCH_ASSOC);

$users = [];
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}

foreach (['walter', 'dev_doctor', 'dev_nurse'] as $username) {
    assertTheatre(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['walter'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];

$roleId = (int)$pdo->query("SELECT id FROM roles WHERE role_name = 'Theatre Staff' LIMIT 1")->fetchColumn();
$deptId = (int)$pdo->query("SELECT id FROM departments WHERE department_name IN ('Theatre','Operating Theatre','Surgical Theatre') ORDER BY CASE department_name WHEN 'Theatre' THEN 0 WHEN 'Operating Theatre' THEN 1 WHEN 'Surgical Theatre' THEN 2 ELSE 3 END, id ASC LIMIT 1")->fetchColumn();
assertTheatre($roleId > 0 && $deptId > 0, 'Theatre role or department is missing.');

$pdo->exec("
    INSERT IGNORE INTO role_permissions (role_id, permission_id)
    SELECT r.id, p.id
    FROM roles r
    INNER JOIN permissions p
    WHERE r.role_name = 'Theatre Staff'
      AND p.permission_key = 'view_encounter'
");

$pdo->prepare('
    INSERT INTO users (
        employee_id, first_name, last_name, gender, phone, email, username, password,
        department_id, role_id, status, created_at
    ) VALUES (
        :employee_id, :first_name, :last_name, :gender, :phone, :email, :username, :password,
        :department_id, :role_id, \'Active\', NOW()
    )
')->execute([
    ':employee_id' => 'DEV-THE-001',
    ':first_name' => 'Dev',
    ':last_name' => 'Theatre',
    ':gender' => null,
    ':phone' => null,
    ':email' => 'dev.theatre@example.com',
    ':username' => 'dev_theatre',
    ':password' => password_hash('theatre123', PASSWORD_DEFAULT),
    ':department_id' => $deptId,
    ':role_id' => $roleId,
]);

$theatre = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username = 'dev_theatre'
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
assertTheatre((bool)$theatre, 'Missing theatre fixture user.');

$patientRows = $pdo->query("
    SELECT id
    FROM patients
    WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002')
    ORDER BY hospital_number
")->fetchAll(PDO::FETCH_COLUMN);

assertTheatre(count($patientRows) === 2, 'Dedicated patient fixtures are missing.');
[$patientId, $otherPatientId] = array_map('intval', $patientRows);

$doctorVisitId = createTheatreEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Doctor', 'DOC-' . time());
$theatreVisitId = createTheatreEncounter($pdo, $theatre, $patientId, $deptId, 'Theatre', 'THE-' . time());
$completedVisitId = createTheatreEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Completed', 'CMP-' . time());

$service = new TheatreService($pdo, null, null, new PermissionService($pdo));

foreach ([
    'view_theatre',
    'create_theatre',
    'edit_theatre',
    'complete_theatre',
] as $permissionKey) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key AND is_active = 1');
    $stmt->execute([':permission_key' => $permissionKey]);
    assertTheatre((int)$stmt->fetchColumn() === 1, 'Missing permission ' . $permissionKey . '.');
}

assertTheatre(in_array('theatre_records', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Theatre record table is missing.');

try {
    $clinical = requireTheatreSuccess($service->create([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'procedure_name' => 'Appendectomy',
        'indication' => 'Acute appendicitis.',
        'preoperative_notes' => 'Nil by mouth.',
        'procedure_details' => 'Open appendectomy performed.',
        'findings' => 'Inflamed appendix.',
        'complications' => 'None.',
        'postoperative_notes' => 'Stable in recovery.',
        'postoperative_plan' => 'Observation and analgesia.',
        'anaesthesia_notes' => 'General anaesthesia used.',
    ], $doctor), 'Clinical theatre create');
    $clinicalRecordId = (int)$clinical['theatre_record_id'];

    $duplicate = $service->create([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'procedure_name' => 'Duplicate theatre record',
        'procedure_details' => 'Should fail.',
    ], $doctor);
    assertTheatre(($duplicate['success'] ?? true) === false, 'Duplicate theatre record was accepted.');

    $viewClinical = $service->getByVisit($doctorVisitId, $doctor);
    assertTheatre($viewClinical !== null && (string)$viewClinical['procedure_name'] === 'Appendectomy', 'Clinical theatre record is not visible.');

    $updated = requireTheatreSuccess($service->update($clinicalRecordId, [
        'procedure_name' => 'Appendectomy',
        'indication' => 'Acute appendicitis with pain.',
        'preoperative_notes' => 'Nil by mouth and consented.',
        'procedure_details' => 'Open appendectomy performed successfully.',
        'findings' => 'Inflamed appendix with no perforation.',
        'complications' => 'None.',
        'postoperative_notes' => 'Stable after surgery.',
        'postoperative_plan' => 'Continue observation.',
        'anaesthesia_notes' => 'General anaesthesia.',
    ], $doctor), 'Clinical theatre update');
    assertTheatre(($updated['success'] ?? false) === true, 'Clinical theatre update failed.');

    $adminUpdate = requireTheatreSuccess($service->update($clinicalRecordId, [
        'procedure_name' => 'Appendectomy',
        'indication' => 'Administrative correction.',
        'preoperative_notes' => 'Updated by admin.',
        'procedure_details' => 'Open appendectomy performed successfully.',
        'findings' => 'Inflamed appendix with no perforation.',
        'complications' => 'None.',
        'postoperative_notes' => 'Stable after surgery.',
        'postoperative_plan' => 'Continue observation.',
        'anaesthesia_notes' => 'General anaesthesia.',
    ], $admin), 'Administrator theatre update');
    assertTheatre(($adminUpdate['success'] ?? false) === true, 'Administrator theatre update failed.');

    $adminRecord = $service->getById($clinicalRecordId, null);
    assertTheatre((int)($adminRecord['surgeon_id'] ?? 0) === (int)$doctor['id'], 'Administrator update changed surgeon attribution.');

    $nurseDenied = $service->create([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'procedure_name' => 'Nurse should not create',
        'procedure_details' => 'Should fail.',
    ], $nurse);
    assertTheatre(($nurseDenied['success'] ?? true) === false, 'Nurse created a theatre record unexpectedly.');

    $mismatchDenied = $service->create([
        'visit_id' => $doctorVisitId,
        'patient_id' => $otherPatientId,
        'procedure_name' => 'Mismatch',
        'procedure_details' => 'Mismatch',
    ], $doctor);
    assertTheatre(($mismatchDenied['success'] ?? true) === false, 'Patient mismatch was accepted.');

    $complete = requireTheatreSuccess($service->complete($clinicalRecordId, $doctor), 'Clinical theatre completion');
    assertTheatre(($complete['success'] ?? false) === true, 'Clinical theatre completion failed.');

    $postCompleteUpdate = $service->update($clinicalRecordId, [
        'procedure_name' => 'Should not update',
        'procedure_details' => 'Should not update',
    ], $doctor);
    assertTheatre(($postCompleteUpdate['success'] ?? true) === false, 'Completed theatre record accepted an update.');

    $completedDenied = $service->create([
        'visit_id' => $completedVisitId,
        'patient_id' => $patientId,
        'procedure_name' => 'Completed encounter',
        'procedure_details' => 'Should fail.',
    ], $doctor);
    assertTheatre(($completedDenied['success'] ?? true) === false, 'Completed encounter accepted a theatre record.');

    $theatreCreate = requireTheatreSuccess($service->create([
        'visit_id' => $theatreVisitId,
        'patient_id' => $patientId,
        'procedure_name' => 'Hernia repair',
        'indication' => 'Symptomatic inguinal hernia.',
        'preoperative_notes' => 'Direct theatre admission.',
        'procedure_details' => 'Laparoscopic repair performed.',
        'findings' => 'Reducible hernia sac.',
        'complications' => 'None.',
        'postoperative_notes' => 'Recovered well.',
        'postoperative_plan' => 'Observe overnight.',
        'anaesthesia_notes' => 'Regional anaesthesia.',
    ], $theatre), 'Direct theatre create');
    $directRecordId = (int)$theatreCreate['theatre_record_id'];

    $worklist = $service->listWorklist($theatre);
    assertTheatre($worklist !== [], 'Theatre worklist did not include the draft record.');

    $visitHistory = $service->listByVisit($theatreVisitId, $theatre);
    assertTheatre(count($visitHistory) >= 1, 'Theatre visit history is missing.');

    $patientHistory = $service->listByPatient($patientId, $admin);
    assertTheatre(count($patientHistory) >= 2, 'Theatre patient history is incomplete.');

    $theatreRecord = $service->getById($directRecordId, $admin);
    assertTheatre($theatreRecord !== null && (string)$theatreRecord['status'] === 'Draft', 'Direct theatre record could not be loaded.');

    $auditCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM audit_logs al
        INNER JOIN visits v ON v.id = al.visit_id
        WHERE v.visit_number LIKE 'P37-%'
          AND al.module = 'Theatre'
          AND al.action IN ('THEATRE_CREATED','THEATRE_UPDATED','THEATRE_COMPLETED')
    ")->fetchColumn();
    assertTheatre($auditCount >= 3, 'Theatre audit events were not written.');

    $eventCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM encounter_events ee
        INNER JOIN visits v ON v.id = ee.visit_id
        WHERE v.visit_number LIKE 'P37-%'
          AND ee.event_type IN ('THEATRE_STARTED','THEATRE_COMPLETED')
    ")->fetchColumn();
    assertTheatre($eventCount >= 2, 'Theatre encounter events were not written.');

    $readOnlyDenied = $service->complete($clinicalRecordId, $doctor);
    assertTheatre(($readOnlyDenied['success'] ?? true) === false, 'Completed theatre record accepted a second completion.');
} finally {
    $pdo->exec("DELETE ce FROM encounter_events ce INNER JOIN visits v ON v.id = ce.visit_id WHERE v.visit_number LIKE 'P37-%'");
    $pdo->exec("DELETE al FROM audit_logs al INNER JOIN visits v ON v.id = al.visit_id WHERE v.visit_number LIKE 'P37-%'");
    $pdo->exec("DELETE tr FROM theatre_records tr INNER JOIN visits v ON v.id = tr.visit_id WHERE v.visit_number LIKE 'P37-%'");
    $pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'P37-%'");
    $pdo->exec("DELETE FROM users WHERE username = 'dev_theatre'");
}

fwrite(STDOUT, "Phase 3.7 Theatre regression passed.\n");
