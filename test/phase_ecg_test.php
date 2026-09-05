<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/ConsultationService.php';
require_once __DIR__ . '/../services/ECGService.php';
require_once __DIR__ . '/../services/PermissionService.php';

function assertEcg(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireEcgSuccess(array $result, string $operation): array
{
    assertEcg(($result['success'] ?? false) === true, $operation . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

function ecgUser(PDO $pdo, string $username): array
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
    assertEcg((bool)$row, 'Missing test user ' . $username . '.');
    return $row;
}

function ecgEnsureTechnician(PDO $pdo): array
{
    $password = password_hash('development-password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('
        INSERT INTO users (
            employee_id, first_name, last_name, gender, email, username,
            password, department_id, role_id, status, must_change_password
        )
        SELECT "DEV-ECG-001", "Development", "ECG", "Female", "dev_ecg@development.invalid",
               "dev_ecg", :password, d.id, r.id, "Active", 0
        FROM departments d
        INNER JOIN roles r
        WHERE d.department_name = "ECG"
          AND r.role_name = "ECG Technician"
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
            WHERE u.username = "dev_ecg"
            ON DUPLICATE KEY UPDATE is_primary = 1, is_active = 1
        ');
    }

    return ecgUser($pdo, 'dev_ecg');
}

function ecgCreateEncounter(PDO $pdo, array $actor, int $patientId, int $departmentId, string $status, string $suffix): int
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
        ':visit_number' => 'ECG-' . $status . '-' . $suffix,
        ':patient_id' => $patientId,
        ':department_id' => $departmentId,
        ':attending_doctor_id' => (int)($actor['id'] ?? 0),
        ':visit_status' => $status,
        ':created_by' => (int)($actor['id'] ?? 0),
    ]);

    return (int)$pdo->lastInsertId();
}

function routeEcgEncounter(PDO $pdo, int $visitId, array $departmentUser): void
{
    $stmt = $pdo->prepare("
        UPDATE visits
        SET current_department_id = :department_id,
            current_department_received_status = 'Received',
            visit_status = 'ECG',
            updated_at = NOW()
        WHERE id = :visit_id
    ");
    $stmt->execute([
        ':department_id' => (int)$departmentUser['department_id'],
        ':visit_id' => $visitId,
    ]);
}

function ecgWorklistContains(array $rows, int $requestId): bool
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
assertEcg(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'ECG tests are not isolated from the live database.'
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/058_ecg_department_crud_up.sql', 58);

$pdo->exec("DELETE ce FROM encounter_events ce INNER JOIN visits v ON v.id = ce.visit_id WHERE v.visit_number LIKE 'ECG-%'");
$pdo->exec("DELETE al FROM audit_logs al INNER JOIN visits v ON v.id = al.visit_id WHERE v.visit_number LIKE 'ECG-%'");
$pdo->exec("DELETE erp FROM ecg_reports erp INNER JOIN ecg_requests er ON er.id = erp.ecg_request_id INNER JOIN visits v ON v.id = er.visit_id WHERE v.visit_number LIKE 'ECG-%'");
$pdo->exec("DELETE er FROM ecg_requests er INNER JOIN visits v ON v.id = er.visit_id WHERE v.visit_number LIKE 'ECG-%'");
$pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'ECG-%'");

$doctor = ecgUser($pdo, 'dev_doctor');
$nurse = ecgUser($pdo, 'dev_nurse');
$ecgTech = ecgEnsureTechnician($pdo);

$patientRows = $pdo->query("
    SELECT id
    FROM patients
    WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002')
    ORDER BY hospital_number
")->fetchAll(PDO::FETCH_COLUMN);
assertEcg(count($patientRows) === 2, 'Dedicated patient fixtures are missing.');
[$patientId, $otherPatientId] = array_map('intval', $patientRows);

$permissionService = new PermissionService($pdo);
$storageRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hms_ecg_test_' . bin2hex(random_bytes(4));
$ecgService = new ECGService($pdo, null, null, $permissionService, $storageRoot);
$consultationService = new ConsultationService($pdo);

$clinicalVisitId = ecgCreateEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Doctor', (string)time());
$directVisitId = ecgCreateEncounter($pdo, $ecgTech, $patientId, (int)$ecgTech['department_id'], 'ECG', (string)(time() + 1));
$completedVisitId = ecgCreateEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Completed', (string)(time() + 2));

$clinical = requireEcgSuccess($ecgService->createRequest([
    'visit_id' => $clinicalVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Clinical',
    'study_requested' => 'ECG',
    'clinical_indication' => 'Chest pain and palpitations.',
    'priority' => 'Urgent',
], $doctor), 'Clinical ECG request');
$clinicalRequestId = (int)$clinical['ecg_request_id'];

$worklist = $ecgService->listWorklist($ecgTech, ['status' => 'Requested']);
if (!ecgWorklistContains($worklist, $clinicalRequestId)) {
    fwrite(STDOUT, 'ECG tech role=' . (string)($ecgTech['role_name'] ?? '') . ' department=' . (string)($ecgTech['department_name'] ?? '') . PHP_EOL);
    fwrite(STDOUT, 'canViewEcgWorklist=' . ($permissionService->canViewEcgWorklist($ecgTech) ? 'yes' : 'no') . PHP_EOL);
    fwrite(STDOUT, 'canViewEcg=' . ($permissionService->canViewEcg($patientId, $ecgTech) ? 'yes' : 'no') . PHP_EOL);
    fwrite(STDOUT, 'worklist rows=' . count($worklist) . PHP_EOL);
}
assertEcg(ecgWorklistContains($worklist, $clinicalRequestId), 'ECG worklist did not show the clinical request.');

routeEcgEncounter($pdo, $clinicalVisitId, $ecgTech);
requireEcgSuccess($ecgService->startRequest($clinicalRequestId, $ecgTech), 'Start ECG request');

$tmpChart = tempnam(sys_get_temp_dir(), 'ecg-');
file_put_contents($tmpChart, "%PDF-1.4\n% ECG test chart\n");
requireEcgSuccess($ecgService->saveReport([
    'ecg_request_id' => $clinicalRequestId,
    'notes' => 'Sinus rhythm seen on scanned chart.',
    'remarks' => 'Doctor to correlate clinically.',
], $ecgTech, [
    'name' => 'ecg-chart.pdf',
    'type' => 'application/pdf',
    'tmp_name' => $tmpChart,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($tmpChart),
]), 'Save ECG chart/report');

requireEcgSuccess($ecgService->completeRequest($clinicalRequestId, $ecgTech), 'Complete ECG request');
$completed = $ecgService->getRequestById($clinicalRequestId, $doctor);
assertEcg($completed !== null && (string)$completed['status'] === 'Completed', 'Completed ECG request was not visible to Doctor.');
assertEcg(!empty($completed['chart_stored_path']) && is_file((string)$completed['chart_stored_path']), 'Stored ECG chart file is missing.');

$readonly = $ecgService->updateReport([
    'ecg_request_id' => $clinicalRequestId,
    'notes' => 'Should not update',
    'remarks' => 'Should not update',
], $ecgTech);
assertEcg(($readonly['success'] ?? true) === false, 'Completed ECG request accepted report update.');

$direct = requireEcgSuccess($ecgService->createRequest([
    'visit_id' => $directVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Direct',
    'study_requested' => 'ECG',
    'clinical_indication' => 'External clinic requested ECG.',
    'priority' => 'Routine',
], $ecgTech), 'Direct ECG request');
assertEcg($consultationService->getByVisit($directVisitId) === null, 'Direct ECG unexpectedly created/required Consultation.');

$mismatch = $ecgService->createRequest([
    'visit_id' => $clinicalVisitId,
    'patient_id' => $otherPatientId,
    'request_source' => 'Clinical',
    'study_requested' => 'ECG',
], $doctor);
assertEcg(($mismatch['success'] ?? true) === false, 'Patient/visit mismatch was accepted.');

$unauthorized = $ecgService->createRequest([
    'visit_id' => $clinicalVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Clinical',
    'study_requested' => 'ECG',
], $nurse);
assertEcg(($unauthorized['success'] ?? true) === false, 'Nurse created an ECG request unexpectedly.');

$locked = $ecgService->createRequest([
    'visit_id' => $completedVisitId,
    'patient_id' => $patientId,
    'request_source' => 'Clinical',
    'study_requested' => 'ECG',
], $doctor);
assertEcg(($locked['success'] ?? true) === false, 'Completed encounter accepted ECG request.');

$auditCount = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action IN ('ECG_REQUEST_CREATED','ECG_REQUEST_COMPLETED')")->fetchColumn();
assertEcg($auditCount > 0, 'ECG audit records were not created.');

$eventCount = (int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE event_type IN ('ECG_REQUESTED','ECG_COMPLETED')")->fetchColumn();
assertEcg($eventCount > 0, 'ECG encounter events were not created.');

fwrite(STDOUT, "ECG workflow tests passed.\n");
