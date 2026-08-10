<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/PhysiotherapyService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/VisitService.php';

function assertPhysio(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function success(array $result, string $label): array
{
    assertPhysio(($result['success'] ?? false) === true, $label . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

function createEncounter(PDO $pdo, array $actor, int $patientId, int $departmentId, string $status, string $suffix): int
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
        ':visit_number' => 'P36-' . $status . '-' . $suffix,
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
assertPhysio(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Phase 3.6 tests are not isolated from the live database.'
);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL .
    'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/028_phase3_physiotherapy_up.sql', 28);
$pdo->exec("
    INSERT IGNORE INTO role_permissions (role_id, permission_id)
    SELECT r.id, p.id
    FROM roles r
    INNER JOIN permissions p
    WHERE r.role_name = 'Physiotherapist'
      AND p.permission_key = 'view_encounter'
");

$pdo->exec("DELETE ce FROM encounter_events ce INNER JOIN visits v ON v.id = ce.visit_id WHERE v.visit_number LIKE 'P36-%'");
$pdo->exec("DELETE al FROM audit_logs al INNER JOIN visits v ON v.id = al.visit_id WHERE v.visit_number LIKE 'P36-%'");
$pdo->exec("DELETE ps FROM physiotherapy_sessions ps INNER JOIN physiotherapy_records pr ON pr.id = ps.physiotherapy_record_id INNER JOIN visits v ON v.id = pr.visit_id WHERE v.visit_number LIKE 'P36-%'");
$pdo->exec("DELETE pr FROM physiotherapy_records pr INNER JOIN visits v ON v.id = pr.visit_id WHERE v.visit_number LIKE 'P36-%'");
$pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'P36-%'");
$pdo->exec("DELETE FROM users WHERE username = 'dev_physio'");

$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('admin','dev_doctor','dev_nurse','dev_records')
")->fetchAll(PDO::FETCH_ASSOC);

$users = [];
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}

foreach (['admin', 'dev_doctor', 'dev_nurse', 'dev_records'] as $username) {
    assertPhysio(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['admin'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];

$roleId = (int)$pdo->query("SELECT id FROM roles WHERE role_name = 'Physiotherapist' LIMIT 1")->fetchColumn();
$deptId = (int)$pdo->query("SELECT id FROM departments WHERE department_name IN ('Physiotherapy','Physio','Rehabilitation') ORDER BY CASE WHEN department_name='Physiotherapy' THEN 0 WHEN department_name='Physio' THEN 1 ELSE 2 END, id ASC LIMIT 1")->fetchColumn();
assertPhysio($roleId > 0 && $deptId > 0, 'Physiotherapy role or department is missing.');

$pdo->prepare('
    INSERT INTO users (
        employee_id, first_name, last_name, gender, phone, email, username, password,
        department_id, role_id, status, created_at
    ) VALUES (
        :employee_id, :first_name, :last_name, :gender, :phone, :email, :username, :password,
        :department_id, :role_id, \'Active\', NOW()
    )
')->execute([
    ':employee_id' => 'DEV-PHY-001',
    ':first_name' => 'Dev',
    ':last_name' => 'Physio',
    ':gender' => null,
    ':phone' => null,
    ':email' => 'dev.physio@example.com',
    ':username' => 'dev_physio',
    ':password' => password_hash('physio123', PASSWORD_DEFAULT),
    ':department_id' => $deptId,
    ':role_id' => $roleId,
]);

$physio = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username = 'dev_physio'
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
assertPhysio((bool)$physio, 'Missing physiotherapy fixture user.');

$patientIds = array_map('intval', $pdo->query("SELECT id FROM patients WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002') ORDER BY hospital_number")->fetchAll(PDO::FETCH_COLUMN));
assertPhysio(count($patientIds) === 2, 'Dedicated patient fixtures are missing.');
[$patientId, $otherPatientId] = $patientIds;

$doctorVisitId = createEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Doctor', 'DOC-' . time());
$physioVisitId = createEncounter($pdo, $physio, $patientId, $deptId, 'Physiotherapy', 'PHY-' . time());
$completedVisitId = createEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Completed', 'CMP-' . time());

$service = new PhysiotherapyService($pdo, null, null, new PermissionService($pdo));

foreach ([
    'view_physiotherapy',
    'create_physiotherapy',
    'edit_physiotherapy',
    'manage_physiotherapy_sessions',
    'complete_physiotherapy',
] as $permissionKey) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key AND is_active = 1');
    $stmt->execute([':permission_key' => $permissionKey]);
    assertPhysio((int)$stmt->fetchColumn() === 1, 'Missing permission ' . $permissionKey . '.');
}

assertPhysio(in_array('physiotherapy_records', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Physiotherapy record table is missing.');
assertPhysio(in_array('physiotherapy_sessions', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Physiotherapy session table is missing.');

try {
    $clinical = success($service->createRecord([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'record_source' => 'Clinical',
        'referral_reason' => 'Persistent lower back pain.',
        'presenting_problem' => 'Reduced mobility and back pain.',
        'assessment' => 'Lumbar strain suspected.',
        'functional_limitations' => 'Pain on bending and lifting.',
        'treatment_plan' => 'Range of motion exercises and strengthening.',
        'goals' => 'Improve mobility.',
        'precautions' => 'Avoid heavy lifting.',
    ], $doctor), 'Clinical physiotherapy record create');
    $clinicalRecordId = (int)$clinical['physiotherapy_record_id'];

    $worklist = $service->listWorklist($physio, ['status' => 'Active']);
    assertPhysio($worklist !== [], 'Physiotherapy worklist did not include the clinical record.');

    $doctorDenied = $service->createRecord([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'record_source' => 'Direct',
        'presenting_problem' => 'Should fail.',
        'assessment' => 'Should fail.',
        'treatment_plan' => 'Should fail.',
    ], $doctor);
    assertPhysio(($doctorDenied['success'] ?? true) === false, 'Doctor created a direct physiotherapy record unexpectedly.');

    $mismatchDenied = $service->createRecord([
        'visit_id' => $doctorVisitId,
        'patient_id' => $otherPatientId,
        'record_source' => 'Clinical',
        'referral_reason' => 'Mismatch',
        'presenting_problem' => 'Mismatch',
        'assessment' => 'Mismatch',
        'treatment_plan' => 'Mismatch',
    ], $doctor);
    assertPhysio(($mismatchDenied['success'] ?? true) === false, 'Patient mismatch was accepted.');

    $sessionCreate = success($service->addSession([
        'physiotherapy_record_id' => $clinicalRecordId,
        'session_date' => date('Y-m-d H:i:s'),
        'treatment_given' => 'Stretching and strengthening exercises.',
        'patient_response' => 'Pain improved slightly.',
        'progress_notes' => 'Tolerated therapy well.',
        'next_plan' => 'Repeat session tomorrow.',
    ], $physio), 'Physiotherapy session create');
    $sessionId = (int)$sessionCreate['physiotherapy_session_id'];

    $sessionUpdate = success($service->updateSession($sessionId, [
        'session_date' => date('Y-m-d H:i:s'),
        'treatment_given' => 'Updated stretching and strengthening exercises.',
        'patient_response' => 'Pain improved further.',
        'progress_notes' => 'Good tolerance.',
        'next_plan' => 'Continue home exercises.',
    ], $physio), 'Physiotherapy session update');
    assertPhysio(($sessionUpdate['success'] ?? false) === true, 'Physiotherapy session update failed.');

    $recordView = $service->getRecordById($clinicalRecordId, $doctor);
    assertPhysio($recordView !== null && (int)($recordView['session_count'] ?? 0) === 1, 'Physiotherapy record summary incorrect.');

    $complete = success($service->completeRecord($clinicalRecordId, $physio), 'Physiotherapy completion');
    assertPhysio(($complete['success'] ?? false) === true, 'Physiotherapy completion failed.');

    $readOnlyDenied = $service->updateRecord($clinicalRecordId, [
        'presenting_problem' => 'Should not edit',
        'assessment' => 'Should not edit',
        'treatment_plan' => 'Should not edit',
    ], $physio);
    assertPhysio(($readOnlyDenied['success'] ?? true) === false, 'Completed physiotherapy record accepted an update.');

    $directCreate = success($service->createRecord([
        'visit_id' => $physioVisitId,
        'patient_id' => $patientId,
        'record_source' => 'Direct',
        'presenting_problem' => 'Rehabilitation following knee injury.',
        'assessment' => 'Reduced knee range of motion.',
        'treatment_plan' => 'Mobility and strengthening sessions.',
    ], $physio), 'Direct physiotherapy record create');
    $directRecordId = (int)$directCreate['physiotherapy_record_id'];

    $directRecord = $service->getRecordById($directRecordId, $physio);
    assertPhysio($directRecord !== null && (string)$directRecord['record_source'] === 'Direct', 'Direct physiotherapy record did not persist.');

    $completeDirect = $service->completeRecord($directRecordId, $physio);
    assertPhysio(($completeDirect['success'] ?? false) === false, 'Direct record completed without a session.');

    success($service->addSession([
        'physiotherapy_record_id' => $directRecordId,
        'session_date' => date('Y-m-d H:i:s'),
        'treatment_given' => 'Manual therapy and gait training.',
    ], $physio), 'Direct physiotherapy session create');
    success($service->completeRecord($directRecordId, $physio), 'Direct physiotherapy completion');

    $nurseDenied = $service->createRecord([
        'visit_id' => $physioVisitId,
        'patient_id' => $patientId,
        'record_source' => 'Direct',
        'presenting_problem' => 'Should fail',
        'assessment' => 'Should fail',
        'treatment_plan' => 'Should fail',
    ], $nurse);
    assertPhysio(($nurseDenied['success'] ?? true) === false, 'Nurse created a physiotherapy record unexpectedly.');

    $completedDenied = $service->createRecord([
        'visit_id' => $completedVisitId,
        'patient_id' => $patientId,
        'record_source' => 'Clinical',
        'presenting_problem' => 'Completed encounter',
        'assessment' => 'Completed encounter',
        'treatment_plan' => 'Completed encounter',
    ], $doctor);
    assertPhysio(($completedDenied['success'] ?? true) === false, 'Completed encounter accepted a physiotherapy record.');

    $auditCount = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action IN ('PHYSIOTHERAPY_CREATED','PHYSIOTHERAPY_SESSION_CREATED','PHYSIOTHERAPY_COMPLETED')")->fetchColumn();
    assertPhysio($auditCount >= 3, 'Expected physiotherapy audit events were not written.');

    $eventCount = (int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE event_type IN ('PHYSIOTHERAPY_STARTED','PHYSIOTHERAPY_COMPLETED')")->fetchColumn();
    assertPhysio($eventCount >= 2, 'Expected physiotherapy encounter events were not written.');

    $history = $service->listByVisit($doctorVisitId, $doctor);
    assertPhysio($history !== [], 'Physiotherapy visit history is empty.');

    echo "Physiotherapy CRUD test passed." . PHP_EOL;
} finally {
    $pdo->exec("DELETE ce FROM encounter_events ce INNER JOIN visits v ON v.id = ce.visit_id WHERE v.visit_number LIKE 'P36-%'");
    $pdo->exec("DELETE al FROM audit_logs al INNER JOIN visits v ON v.id = al.visit_id WHERE v.visit_number LIKE 'P36-%'");
    $pdo->exec("DELETE ps FROM physiotherapy_sessions ps INNER JOIN physiotherapy_records pr ON pr.id = ps.physiotherapy_record_id INNER JOIN visits v ON v.id = pr.visit_id WHERE v.visit_number LIKE 'P36-%'");
    $pdo->exec("DELETE pr FROM physiotherapy_records pr INNER JOIN visits v ON v.id = pr.visit_id WHERE v.visit_number LIKE 'P36-%'");
    $pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'P36-%'");
    $pdo->exec("DELETE FROM users WHERE username = 'dev_physio'");
}
