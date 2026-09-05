<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/ConsultationService.php';
require_once __DIR__ . '/../services/LaboratoryService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/VisitService.php';
require_once __DIR__ . '/../services/VitalSignsService.php';
require_once __DIR__ . '/../services/NursingService.php';

function assertLaboratory(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireLaboratorySuccess(array $result, string $operation): array
{
    assertLaboratory(
        ($result['success'] ?? false) === true,
        $operation . ': ' . implode(' ', $result['errors'] ?? [])
    );

    return $result;
}

function createLaboratoryEncounter(PDO $pdo, array $actor, int $patientId, int $departmentId, string $status, string $suffix): int
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
        ':visit_number' => 'P34-' . $status . '-' . $suffix,
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

function routeLaboratoryEncounter(PDO $pdo, int $visitId, array $departmentUser): void
{
    $stmt = $pdo->prepare("
        UPDATE visits
        SET current_department_id = :department_id,
            current_department_received_status = 'Received',
            visit_status = 'Laboratory',
            updated_at = NOW()
        WHERE id = :visit_id
    ");
    $stmt->execute([
        ':department_id' => (int)$departmentUser['department_id'],
        ':visit_id' => $visitId,
    ]);
}

function fileContains(string $path, string $needle): bool
{
    $contents = file_get_contents($path);
    return $contents !== false && str_contains($contents, $needle);
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertLaboratory(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Phase 3.4 tests are not isolated from the live database.'
);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL
    . 'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/025_phase3_laboratory_up.sql', 25);
$manager->apply(__DIR__ . '/../database/migrations/026_phase3_laboratory_result_details_up.sql', 26);

$pdo->exec("
    DELETE ce
    FROM encounter_events ce
    INNER JOIN visits v ON v.id = ce.visit_id
    WHERE v.visit_number LIKE 'P34-%'
");
$pdo->exec("
    DELETE al
    FROM audit_logs al
    INNER JOIN visits v ON v.id = al.visit_id
    WHERE v.visit_number LIKE 'P34-%'
");
$pdo->exec("
    DELETE lr
    FROM laboratory_results lr
    INNER JOIN laboratory_requests lq ON lq.id = lr.laboratory_request_id
    INNER JOIN visits v ON v.id = lq.visit_id
    WHERE v.visit_number LIKE 'P34-%'
");
$pdo->exec("
    DELETE lq
    FROM laboratory_requests lq
    INNER JOIN visits v ON v.id = lq.visit_id
    WHERE v.visit_number LIKE 'P34-%'
");
$pdo->exec("
    DELETE c
    FROM consultations c
    INNER JOIN visits v ON v.id = c.visit_id
    WHERE v.visit_number LIKE 'P34-%'
");
$pdo->exec("
    DELETE vs
    FROM vital_signs vs
    INNER JOIN visits v ON v.id = vs.visit_id
    WHERE v.visit_number LIKE 'P34-%'
");
$pdo->exec("
    DELETE na
    FROM nursing_assessments na
    INNER JOIN visits v ON v.id = na.visit_id
    WHERE v.visit_number LIKE 'P34-%'
");
$pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'P34-%'");

$users = [];
$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('walter','dev_doctor','dev_nurse','dev_records','dev_laboratory')
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}

foreach (['walter', 'dev_doctor', 'dev_nurse', 'dev_records', 'dev_laboratory'] as $username) {
    assertLaboratory(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['walter'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];
$records = $users['dev_records'];
$lab = $users['dev_laboratory'];

$patients = array_map(
    'intval',
    $pdo->query("
        SELECT id
        FROM patients
        WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002')
        ORDER BY hospital_number
    ")->fetchAll(PDO::FETCH_COLUMN)
);
assertLaboratory(count($patients) === 2, 'Dedicated patient fixtures are missing.');
[$patientId, $otherPatientId] = $patients;

$visitIds = [];
$requestIds = [];
$resultIds = [];

$doctorVisitId = null;
$labVisitId = null;
$completedVisitId = null;
$cancelledVisitId = null;
$adminVisitId = null;

try {
    foreach ([
        'view_laboratory',
        'create_laboratory_request',
        'process_laboratory_request',
        'enter_laboratory_result',
        'edit_laboratory_result',
        'complete_laboratory_request'
    ] as $permissionKey) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key AND is_active = 1');
        $stmt->execute([':permission_key' => $permissionKey]);
        assertLaboratory((int)$stmt->fetchColumn() === 1, 'Missing permission ' . $permissionKey . '.');
    }
    assertLaboratory(in_array('laboratory_requests', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Laboratory request table is missing.');
    assertLaboratory(in_array('laboratory_results', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Laboratory result table is missing.');
    $resultColumns = $pdo->query("SHOW COLUMNS FROM laboratory_results")->fetchAll(PDO::FETCH_COLUMN);
    assertLaboratory(in_array('sample_taken', $resultColumns, true), 'Laboratory result sample_taken column is missing.');
    assertLaboratory(in_array('findings', $resultColumns, true), 'Laboratory result findings column is missing.');

    $doctorVisitId = createLaboratoryEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Doctor', 'DOC-' . time());
    $labVisitId = createLaboratoryEncounter($pdo, $lab, $patientId, (int)$lab['department_id'], 'Laboratory', 'LAB-' . time());
    $completedVisitId = createLaboratoryEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Completed', 'CMP-' . time());
    $cancelledVisitId = createLaboratoryEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Cancelled', 'CAN-' . time());
    $adminVisitId = createLaboratoryEncounter($pdo, $admin, $patientId, (int)$doctor['department_id'], 'Completed', 'ADM-' . time());
    $visitIds = [$doctorVisitId, $labVisitId, $completedVisitId, $cancelledVisitId, $adminVisitId];

    $laboratoryService = new LaboratoryService($pdo, null, null, new PermissionService($pdo));
    $visitService = new VisitService($pdo);
    $consultationService = new ConsultationService($pdo);
    $vitalSignsService = new VitalSignsService($pdo, null, new PermissionService($pdo));
    $nursingService = new NursingService($pdo, null, null, new PermissionService($pdo));

    $clinicalCreate = requireLaboratorySuccess($laboratoryService->createRequest([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'priority' => 'Routine',
        'tests_requested' => 'Malaria Parasite, Full Blood Count',
        'clinical_information' => 'Fever for 3 days; suspected malaria.',
    ], $doctor), 'Clinical request create');
    $clinicalRequestId = (int)$clinicalCreate['laboratory_request_id'];
    $requestIds[] = $clinicalRequestId;

    $clinicalRequest = $laboratoryService->getRequestById($clinicalRequestId, $doctor);
    assertLaboratory($clinicalRequest !== null, 'Clinical request could not be loaded.');
    assertLaboratory((string)$clinicalRequest['status'] === 'Requested', 'Clinical request status is incorrect.');

    assertLaboratory($laboratoryService->listByVisit($doctorVisitId, $doctor) !== [], 'Visit laboratory history is empty.');
    assertLaboratory($laboratoryService->listByPatient($patientId, $doctor) !== [], 'Patient laboratory history is empty.');
    assertLaboratory($laboratoryService->listWorklist($lab, ['status' => 'Requested']) !== [], 'Worklist did not include the created request.');

    routeLaboratoryEncounter($pdo, $doctorVisitId, $lab);
    $startClinical = requireLaboratorySuccess($laboratoryService->startRequest($clinicalRequestId, $lab), 'Clinical request start');
    assertLaboratory(($startClinical['success'] ?? false) === true, 'Clinical request start failed.');

    $saveClinical = requireLaboratorySuccess($laboratoryService->saveResult([
        'laboratory_request_id' => $clinicalRequestId,
        'sample_taken' => 'Blood sample',
        'findings' => 'Thick smear positive.',
        'result' => "Malaria parasites detected (++).\nHb: 11.2 g/dL",
        'interpretation' => 'Positive for malaria parasites.',
    ], $lab), 'Clinical result save');
    $clinicalResultId = (int)$saveClinical['laboratory_request_id'];
    $resultIds[] = $clinicalResultId;

    $resultUpdate = requireLaboratorySuccess($laboratoryService->updateResult([
        'laboratory_request_id' => $clinicalRequestId,
        'sample_taken' => 'Repeat blood sample',
        'findings' => 'Repeat smear remains positive.',
        'result' => "Malaria parasites detected (++).\nHb: 11.0 g/dL",
        'interpretation' => 'Updated interpretation.',
    ], $lab), 'Clinical result update');
    assertLaboratory(($resultUpdate['success'] ?? false) === true, 'Result update failed.');

    $completeClinical = requireLaboratorySuccess($laboratoryService->completeRequest($clinicalRequestId, $lab), 'Clinical request complete');
    assertLaboratory(($completeClinical['success'] ?? false) === true, 'Clinical request completion failed.');

    $viewResult = $laboratoryService->getResult($clinicalRequestId, $doctor);
    assertLaboratory($viewResult !== null && trim((string)$viewResult['result']) !== '', 'Clinical result is not visible.');

    $directCreate = requireLaboratorySuccess($laboratoryService->createRequest([
        'visit_id' => $labVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Direct',
        'priority' => 'Urgent',
        'tests_requested' => 'Blood Glucose',
        'clinical_information' => 'Direct laboratory walk-in.',
    ], $lab), 'Direct request create');
    $directRequestId = (int)$directCreate['laboratory_request_id'];
    $requestIds[] = $directRequestId;

    $directResult = requireLaboratorySuccess($laboratoryService->saveResult([
        'laboratory_request_id' => $directRequestId,
        'sample_taken' => 'Capillary blood',
        'findings' => 'Glucose sample obtained.',
        'result' => 'Blood Glucose: 5.3 mmol/L',
        'interpretation' => 'Within normal range.',
    ], $lab), 'Direct result save');
    $resultIds[] = (int)$directResult['laboratory_request_id'];

    requireLaboratorySuccess($laboratoryService->completeRequest($directRequestId, $lab), 'Direct request complete');

    $adminCompletedCreate = requireLaboratorySuccess($laboratoryService->createRequest([
        'visit_id' => $completedVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'priority' => 'Routine',
        'tests_requested' => 'Administrator test entry',
        'clinical_information' => 'Development override.',
    ], $admin), 'Administrator create on completed encounter');
    $adminRequestId = (int)$adminCompletedCreate['laboratory_request_id'];
    $requestIds[] = $adminRequestId;

    $doctorDenied = $laboratoryService->createRequest([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Direct',
        'priority' => 'Routine',
        'tests_requested' => 'Should fail.',
        'clinical_information' => 'Unauthorized.',
    ], $doctor);
    assertLaboratory(($doctorDenied['success'] ?? true) === false, 'Doctor direct laboratory request was accepted.');

    $nurseView = $laboratoryService->getRequestById($clinicalRequestId, $nurse);
    assertLaboratory($nurseView !== null, 'Nurse should be able to view laboratory requests.');

    $recordsCreate = $laboratoryService->createRequest([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'priority' => 'Routine',
        'tests_requested' => 'Should fail.',
        'clinical_information' => 'Unauthorized mutation.',
    ], $records);
    assertLaboratory(($recordsCreate['success'] ?? true) === false, 'Unauthorized mutation was accepted.');

    $mismatch = $laboratoryService->createRequest([
        'visit_id' => $doctorVisitId,
        'patient_id' => $otherPatientId,
        'request_source' => 'Clinical',
        'priority' => 'Routine',
        'tests_requested' => 'Mismatch should fail.',
        'clinical_information' => 'Cross patient request.',
    ], $doctor);
    assertLaboratory(($mismatch['success'] ?? true) === false, 'Cross-patient request was accepted.');

    $closedCreate = $laboratoryService->createRequest([
        'visit_id' => $completedVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'priority' => 'Routine',
        'tests_requested' => 'Closed encounter should fail.',
        'clinical_information' => 'Completed encounter.',
    ], $doctor);
    assertLaboratory(($closedCreate['success'] ?? true) === false, 'Completed encounter accepted a request.');

    $cancelledCreate = $laboratoryService->createRequest([
        'visit_id' => $cancelledVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'priority' => 'Routine',
        'tests_requested' => 'Cancelled encounter should fail.',
        'clinical_information' => 'Cancelled encounter.',
    ], $doctor);
    assertLaboratory(($cancelledCreate['success'] ?? true) === false, 'Cancelled encounter accepted a request.');

    $readonlyUpdate = $laboratoryService->updateResult([
        'laboratory_request_id' => $clinicalRequestId,
        'result' => 'Should not update after completion.',
        'interpretation' => 'Read-only test.',
    ], $lab);
    assertLaboratory(($readonlyUpdate['success'] ?? true) === false, 'Completed laboratory request accepted an edit.');

    $readonlyStart = $laboratoryService->startRequest($clinicalRequestId, $lab);
    assertLaboratory(($readonlyStart['success'] ?? true) === false, 'Completed laboratory request accepted a restart.');

    $readonlyCancel = $laboratoryService->cancelRequest($clinicalRequestId, $lab);
    assertLaboratory(($readonlyCancel['success'] ?? true) === false, 'Completed laboratory request accepted a cancel.');

    $auditCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM audit_logs
        WHERE visit_id IN ($doctorVisitId, $labVisitId, $adminVisitId)
          AND action IN (
              'LABORATORY_REQUEST_CREATED',
              'LABORATORY_REQUEST_STARTED',
              'LABORATORY_RESULT_CREATED',
              'LABORATORY_RESULT_UPDATED',
              'LABORATORY_REQUEST_COMPLETED',
              'LABORATORY_REQUEST_CANCELLED'
          )
    ")->fetchColumn();
    assertLaboratory($auditCount >= 5, 'Laboratory audit entries are missing.');

    $eventCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM encounter_events
        WHERE visit_id IN ($doctorVisitId, $labVisitId)
          AND event_type IN ('LABORATORY_REQUESTED', 'LABORATORY_COMPLETED')
    ")->fetchColumn();
    assertLaboratory($eventCount >= 4, 'Laboratory encounter events are missing.');

    assertLaboratory(fileContains(__DIR__ . '/../modules/visits/workspace.php', "case 'laboratory'"), 'Workspace missing laboratory tab routing.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/visits/partials/workspace_navigation.php', 'Laboratory'), 'Workspace navigation missing laboratory entry.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/visits/partials/tabs/laboratory.php', 'Request Laboratory Test'), 'Workspace laboratory tab missing request action.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/consultation/create.php', 'Request Laboratory Test'), 'Consultation create missing laboratory request action.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/consultation/view.php', 'Laboratory Requests'), 'Consultation view missing laboratory request summary.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/medical_records/chart.php', "'laboratory'"), 'Patient chart missing laboratory tab gate.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/medical_records/partials/chart_navigation.php', 'Laboratory'), 'Patient chart navigation missing laboratory tab.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/medical_records/partials/laboratory.php', 'No laboratory requests recorded.'), 'Patient chart laboratory partial missing empty state.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/laboratory/create.php', 'Create Laboratory Request'), 'Laboratory create page is missing.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/laboratory/view.php', 'Laboratory Request #'), 'Laboratory view page is missing.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/laboratory/index.php', 'Laboratory Worklist'), 'Laboratory worklist page is missing.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/laboratory/history.php', 'Laboratory History'), 'Laboratory history page is missing.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/laboratory/_form.php', 'Tests Requested'), 'Laboratory form missing request fields.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/laboratory/result.php', 'Laboratory Result'), 'Laboratory result page is missing.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/laboratory/save.php', 'requireCsrfToken'), 'Laboratory save route missing CSRF protection.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/laboratory/result_save.php', 'requireCsrfToken'), 'Laboratory result save route missing CSRF protection.');
    assertLaboratory(fileContains(__DIR__ . '/../modules/laboratory/complete.php', 'requireCsrfToken'), 'Laboratory complete route missing CSRF protection.');

    $consultationChart = $visitService->getVisitById($doctorVisitId);
    assertLaboratory($consultationChart !== null, 'VisitService regression check failed.');
    $vitalSignsRegression = $vitalSignsService->listByVisit($doctorVisitId, $doctor);
    assertLaboratory(is_array($vitalSignsRegression), 'Vital Signs regression check failed.');
    $nursingRegression = $nursingService->listByVisit($doctorVisitId, $doctor);
    assertLaboratory(is_array($nursingRegression), 'Nursing regression check failed.');
    $consultationRegression = $consultationService->getByVisit($doctorVisitId);
    assertLaboratory($consultationRegression === null || is_array($consultationRegression), 'Consultation regression check failed.');

    echo 'PASS: Phase 3.4 Laboratory CRUD, permissions, read models, and integration hooks.' . PHP_EOL;
} finally {
    $cleanupVisitIds = array_values(array_filter([
        $doctorVisitId,
        $labVisitId,
        $completedVisitId,
        $cancelledVisitId,
        $adminVisitId
    ], static fn ($value): bool => is_int($value) && $value > 0));

    if ($cleanupVisitIds !== []) {
        $visitList = implode(',', array_map('intval', $cleanupVisitIds));
        $pdo->exec("DELETE FROM encounter_events WHERE visit_id IN ($visitList)");
        $pdo->exec("DELETE FROM audit_logs WHERE visit_id IN ($visitList) AND action IN ('LABORATORY_REQUEST_CREATED','LABORATORY_REQUEST_STARTED','LABORATORY_RESULT_CREATED','LABORATORY_RESULT_UPDATED','LABORATORY_REQUEST_COMPLETED','LABORATORY_REQUEST_CANCELLED')");
    }
    if ($requestIds !== []) {
        $ids = implode(',', array_map('intval', $requestIds));
        $pdo->exec("DELETE FROM laboratory_results WHERE laboratory_request_id IN ($ids)");
        $pdo->exec("DELETE FROM laboratory_requests WHERE id IN ($ids)");
    }
    if ($visitIds !== []) {
        $pdo->exec("DELETE FROM visits WHERE id IN (" . implode(',', array_map('intval', $visitIds)) . ")");
    }
}
