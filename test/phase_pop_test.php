<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/ConsultationService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/POPService.php';

function assertPop(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requirePopSuccess(array $result, string $operation): array
{
    assertPop(($result['success'] ?? false) === true, $operation . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

function popUser(PDO $pdo, string $username): array
{
    $stmt = $pdo->prepare('
        SELECT u.*, r.role_name, d.department_name, d.department_name AS active_department_name
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        INNER JOIN departments d ON d.id = u.department_id
        WHERE u.username = :username
        LIMIT 1
    ');
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    assertPop((bool)$row, 'Missing test user ' . $username . '.');
    return $row;
}

function popEnsureTechnician(PDO $pdo): array
{
    $password = password_hash('development-password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('
        INSERT INTO users (
            employee_id, first_name, last_name, gender, email, username,
            password, department_id, role_id, status, must_change_password
        )
        SELECT "DEV-POP-001", "Development", "POP", "Female", "dev_pop@development.invalid",
               "dev_pop", :password, d.id, r.id, "Active", 0
        FROM departments d
        INNER JOIN roles r
        WHERE d.department_name = "POP"
          AND r.role_name = "POP Technician"
        ON DUPLICATE KEY UPDATE
            department_id = VALUES(department_id),
            role_id = VALUES(role_id),
            status = "Active",
            must_change_password = 0
    ');
    $stmt->execute([':password' => $password]);

    if (in_array('user_departments', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true)) {
        $pdo->exec('
            INSERT INTO user_departments (user_id, department_id, is_primary, is_active, assigned_by)
            SELECT u.id, u.department_id, 1, 1, 1
            FROM users u
            WHERE u.username = "dev_pop"
            ON DUPLICATE KEY UPDATE is_primary = 1, is_active = 1
        ');
    }

    return popUser($pdo, 'dev_pop');
}

function popCreateEncounter(PDO $pdo, array $actor, int $patientId, int $departmentId, string $status, string $suffix): int
{
    $stmt = $pdo->prepare('
        INSERT INTO visits (
            visit_number, patient_id, visit_date, visit_type, current_department_id,
            attending_doctor_id, current_department_received_status, visit_status, created_by
        ) VALUES (
            :visit_number, :patient_id, NOW(), "Outpatient", :department_id,
            :attending_doctor_id, "Received", :visit_status, :created_by
        )
    ');
    $stmt->execute([
        ':visit_number' => 'POP-' . $status . '-' . $suffix,
        ':patient_id' => $patientId,
        ':department_id' => $departmentId,
        ':attending_doctor_id' => (int)($actor['id'] ?? 0),
        ':visit_status' => $status,
        ':created_by' => (int)($actor['id'] ?? 0),
    ]);

    return (int)$pdo->lastInsertId();
}

function routePopEncounter(PDO $pdo, int $visitId, array $departmentUser): void
{
    $stmt = $pdo->prepare("
        UPDATE visits
        SET current_department_id = :department_id,
            current_department_received_status = 'Received',
            visit_status = 'POP',
            updated_at = NOW()
        WHERE id = :visit_id
    ");
    $stmt->execute([
        ':department_id' => (int)$departmentUser['department_id'],
        ':visit_id' => $visitId,
    ]);
}

function popWorklistContains(array $rows, int $requestId): bool
{
    foreach ($rows as $row) {
        if ((int)($row['id'] ?? 0) === $requestId) {
            return true;
        }
    }
    return false;
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertPop($databaseName === $resolved['test'] && $databaseName !== $resolved['live'], 'POP tests are not isolated from the live database.');

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/059_pop_department_crud_up.sql', 59);

$pdo->exec("DELETE ce FROM encounter_events ce INNER JOIN visits v ON v.id = ce.visit_id WHERE v.visit_number LIKE 'POP-%'");
$pdo->exec("DELETE al FROM audit_logs al INNER JOIN visits v ON v.id = al.visit_id WHERE v.visit_number LIKE 'POP-%'");
$pdo->exec("DELETE prc FROM pop_records prc INNER JOIN pop_requests pr ON pr.id = prc.pop_request_id INNER JOIN visits v ON v.id = pr.visit_id WHERE v.visit_number LIKE 'POP-%'");
$pdo->exec("DELETE pr FROM pop_requests pr INNER JOIN visits v ON v.id = pr.visit_id WHERE v.visit_number LIKE 'POP-%'");
$pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'POP-%'");

$doctor = popUser($pdo, 'dev_doctor');
$nurse = popUser($pdo, 'dev_nurse');
$popTech = popEnsureTechnician($pdo);

$patientRows = $pdo->query("
    SELECT id
    FROM patients
    WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002')
    ORDER BY hospital_number
")->fetchAll(PDO::FETCH_COLUMN);
assertPop(count($patientRows) === 2, 'Dedicated patient fixtures are missing.');
[$patientId, $otherPatientId] = array_map('intval', $patientRows);

$permissionService = new PermissionService($pdo);
$popService = new POPService($pdo, null, null, $permissionService);
$consultationService = new ConsultationService($pdo);

$clinicalVisitId = popCreateEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Doctor', (string)time());
$directVisitId = popCreateEncounter($pdo, $popTech, $patientId, (int)$popTech['department_id'], 'POP', (string)(time() + 1));
$completedVisitId = popCreateEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Completed', (string)(time() + 2));

$clinical = requirePopSuccess($popService->createRequest([
    'visit_id' => $clinicalVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Clinical',
    'procedure_requested' => 'Below-knee POP cast',
    'clinical_indication' => 'Suspected ankle fracture.',
    'priority' => 'Urgent',
], $doctor), 'Clinical POP request');
$clinicalRequestId = (int)$clinical['pop_request_id'];
assertPop(popWorklistContains($popService->listWorklist($popTech, ['status' => 'Requested']), $clinicalRequestId), 'POP worklist did not show the clinical request.');

routePopEncounter($pdo, $clinicalVisitId, $popTech);
requirePopSuccess($popService->startRequest($clinicalRequestId, $popTech), 'Start POP request');
requirePopSuccess($popService->saveRecord([
    'pop_request_id' => $clinicalRequestId,
    'cast_type' => 'Below-knee POP',
    'body_part' => 'Right ankle',
    'procedure_notes' => 'POP cast applied with limb supported and circulation checked.',
    'materials_used' => 'POP rolls, cotton wool, bandage.',
    'aftercare_instructions' => 'Keep dry and return immediately if swelling or numbness occurs.',
    'remarks' => 'Tolerated procedure.',
], $popTech), 'Save POP record');
requirePopSuccess($popService->updateRecord([
    'pop_request_id' => $clinicalRequestId,
    'cast_type' => 'Below-knee POP',
    'body_part' => 'Right ankle',
    'procedure_notes' => 'POP cast applied; distal circulation and sensation intact.',
    'materials_used' => 'POP rolls, cotton wool, bandage.',
    'aftercare_instructions' => 'Keep dry and elevate limb.',
    'remarks' => 'Reviewed after setting.',
], $popTech), 'Update POP record');
requirePopSuccess($popService->completeRequest($clinicalRequestId, $popTech), 'Complete POP request');
$completed = $popService->getRequestById($clinicalRequestId, $doctor);
assertPop($completed !== null && (string)$completed['status'] === 'Completed', 'Completed POP request was not visible to Doctor.');

$readonly = $popService->updateRecord([
    'pop_request_id' => $clinicalRequestId,
    'procedure_notes' => 'Should not update',
], $popTech);
assertPop(($readonly['success'] ?? true) === false, 'Completed POP request accepted record update.');

$direct = requirePopSuccess($popService->createRequest([
    'visit_id' => $directVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Direct',
    'procedure_requested' => 'Arm sling / POP review',
    'clinical_indication' => 'Direct POP attendance.',
    'priority' => 'Routine',
], $popTech), 'Direct POP request');
assertPop($consultationService->getByVisit($directVisitId) === null, 'Direct POP unexpectedly created/required Consultation.');

$mismatch = $popService->createRequest([
    'visit_id' => $clinicalVisitId,
    'patient_id' => $otherPatientId,
    'request_source' => 'Clinical',
    'procedure_requested' => 'POP cast',
], $doctor);
assertPop(($mismatch['success'] ?? true) === false, 'Patient/visit mismatch was accepted.');

$unauthorized = $popService->createRequest([
    'visit_id' => $clinicalVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Clinical',
    'procedure_requested' => 'POP cast',
], $nurse);
assertPop(($unauthorized['success'] ?? true) === false, 'Nurse created a POP request unexpectedly.');

$locked = $popService->createRequest([
    'visit_id' => $completedVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Clinical',
    'procedure_requested' => 'POP cast',
], $doctor);
assertPop(($locked['success'] ?? true) === false, 'Completed encounter accepted POP request.');

$auditCount = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action IN ('POP_REQUEST_CREATED','POP_REQUEST_COMPLETED')")->fetchColumn();
assertPop($auditCount > 0, 'POP audit records were not created.');

$eventCount = (int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE event_type IN ('POP_REQUESTED','POP_COMPLETED')")->fetchColumn();
assertPop($eventCount > 0, 'POP encounter events were not created.');

fwrite(STDOUT, "POP workflow tests passed.\n");
