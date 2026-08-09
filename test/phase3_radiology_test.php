<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/ConsultationService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/RadiologyService.php';
require_once __DIR__ . '/../services/VitalSignsService.php';
require_once __DIR__ . '/../services/NursingService.php';
require_once __DIR__ . '/../services/VisitService.php';

function assertRadiology(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireRadiologySuccess(array $result, string $operation): array
{
    assertRadiology(($result['success'] ?? false) === true, $operation . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

function createRadiologyEncounter(PDO $pdo, array $actor, int $patientId, int $departmentId, string $status, string $suffix): int
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
        ':visit_number' => 'P35-' . $status . '-' . $suffix,
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

function fileContains(string $path, string $needle): bool
{
    $contents = file_get_contents($path);
    return $contents !== false && str_contains($contents, $needle);
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertRadiology(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Phase 3.5 tests are not isolated from the live database.'
);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL
    . 'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/027_phase3_radiology_up.sql', 27);

$pdo->exec("DELETE ce FROM encounter_events ce INNER JOIN visits v ON v.id = ce.visit_id WHERE v.visit_number LIKE 'P35-%'");
$pdo->exec("DELETE al FROM audit_logs al INNER JOIN visits v ON v.id = al.visit_id WHERE v.visit_number LIKE 'P35-%'");
$pdo->exec("DELETE rr FROM radiology_reports rr INNER JOIN radiology_requests rq ON rq.id = rr.radiology_request_id INNER JOIN visits v ON v.id = rq.visit_id WHERE v.visit_number LIKE 'P35-%'");
$pdo->exec("DELETE rq FROM radiology_requests rq INNER JOIN visits v ON v.id = rq.visit_id WHERE v.visit_number LIKE 'P35-%'");
$pdo->exec("DELETE c FROM consultations c INNER JOIN visits v ON v.id = c.visit_id WHERE v.visit_number LIKE 'P35-%'");
$pdo->exec("DELETE vs FROM vital_signs vs INNER JOIN visits v ON v.id = vs.visit_id WHERE v.visit_number LIKE 'P35-%'");
$pdo->exec("DELETE na FROM nursing_assessments na INNER JOIN visits v ON v.id = na.visit_id WHERE v.visit_number LIKE 'P35-%'");
$pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'P35-%'");

$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('admin','dev_doctor','dev_nurse','dev_records','dev_radiology')
")->fetchAll(PDO::FETCH_ASSOC);

$users = [];
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}

foreach (['admin', 'dev_doctor', 'dev_nurse', 'dev_records', 'dev_radiology'] as $username) {
    assertRadiology(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['admin'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];
$records = $users['dev_records'];
$radiographer = $users['dev_radiology'];

$patientRows = $pdo->query("
    SELECT id
    FROM patients
    WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002')
    ORDER BY hospital_number
")->fetchAll(PDO::FETCH_COLUMN);

assertRadiology(count($patientRows) === 2, 'Dedicated patient fixtures are missing.');
[$patientId, $otherPatientId] = array_map('intval', $patientRows);

$requestIds = [];
$visitIds = [];

try {
    foreach ([
        'view_radiology',
        'create_radiology_request',
        'process_radiology_request',
        'enter_radiology_report',
        'edit_radiology_report',
        'complete_radiology_request',
    ] as $permissionKey) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key AND is_active = 1');
        $stmt->execute([':permission_key' => $permissionKey]);
        assertRadiology((int)$stmt->fetchColumn() === 1, 'Missing permission ' . $permissionKey . '.');
    }

    assertRadiology(in_array('radiology_requests', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Radiology request table is missing.');
    assertRadiology(in_array('radiology_reports', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Radiology report table is missing.');

    $clinicalVisitId = createRadiologyEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Doctor', 'DOC-' . time());
    $radiologyVisitId = createRadiologyEncounter($pdo, $radiographer, $patientId, (int)$radiographer['department_id'], 'Radiology', 'RAD-' . time());
    $completedVisitId = createRadiologyEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Completed', 'CMP-' . time());
    $visitIds = [$clinicalVisitId, $radiologyVisitId, $completedVisitId];

    $radiologyService = new RadiologyService($pdo, null, null, new PermissionService($pdo));
    $consultationService = new ConsultationService($pdo);
    $vitalSignsService = new VitalSignsService($pdo, null, new PermissionService($pdo));
    $nursingService = new NursingService($pdo, null, null, new PermissionService($pdo));

    $clinicalCreate = requireRadiologySuccess($radiologyService->createRequest([
        'visit_id' => $clinicalVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'priority' => 'Routine',
        'study_requested' => 'Chest X-ray',
        'clinical_indication' => 'Persistent cough, fever, and shortness of breath.',
    ], $doctor), 'Clinical radiology create');
    $clinicalRequestId = (int)$clinicalCreate['radiology_request_id'];
    $requestIds[] = $clinicalRequestId;

    $clinicalRequest = $radiologyService->getRequestById($clinicalRequestId, $doctor);
    assertRadiology($clinicalRequest !== null, 'Clinical radiology request could not be loaded.');
    assertRadiology((string)$clinicalRequest['status'] === 'Requested', 'Clinical request status is incorrect.');

    $worklist = $radiologyService->listWorklist($radiographer, ['status' => 'Requested']);
    assertRadiology($worklist !== [], 'Worklist did not include the clinical request.');

    $notAllowed = $radiologyService->createRequest([
        'visit_id' => $clinicalVisitId,
        'patient_id' => $otherPatientId,
        'request_source' => 'Clinical',
        'priority' => 'Routine',
        'study_requested' => 'Wrong patient',
        'clinical_indication' => 'Mismatch check.',
    ], $doctor);
    assertRadiology(($notAllowed['success'] ?? true) === false, 'Patient/visit mismatch was accepted.');

    $startClinical = requireRadiologySuccess($radiologyService->startRequest($clinicalRequestId, $radiographer), 'Clinical radiology start');
    assertRadiology(($startClinical['success'] ?? false) === true, 'Clinical radiology start failed.');

    $saveReport = requireRadiologySuccess($radiologyService->saveResult([
        'radiology_request_id' => $clinicalRequestId,
        'findings' => 'No focal lung consolidation.',
        'impression' => 'No acute cardiopulmonary abnormality.',
        'recommendation' => 'Clinical correlation advised.',
    ], $radiographer), 'Clinical radiology report save');
    assertRadiology(($saveReport['success'] ?? false) === true, 'Clinical radiology report save failed.');

    $updateReport = requireRadiologySuccess($radiologyService->updateResult([
        'radiology_request_id' => $clinicalRequestId,
        'findings' => 'No focal lung consolidation. Cardiac silhouette is within normal limits.',
        'impression' => 'No acute cardiopulmonary abnormality.',
        'recommendation' => 'Clinical correlation advised.',
    ], $radiographer), 'Clinical radiology report update');
    assertRadiology(($updateReport['success'] ?? false) === true, 'Clinical radiology report update failed.');

    $completeClinical = requireRadiologySuccess($radiologyService->completeRequest($clinicalRequestId, $radiographer), 'Clinical radiology complete');
    assertRadiology(($completeClinical['success'] ?? false) === true, 'Clinical radiology completion failed.');

    $viewReport = $radiologyService->getResult($clinicalRequestId, $doctor);
    assertRadiology($viewReport !== null && trim((string)($viewReport['impression'] ?? '')) !== '', 'Clinical radiology report is not visible.');

    $clinicalReadOnly = $radiologyService->updateResult([
        'radiology_request_id' => $clinicalRequestId,
        'findings' => 'Read only',
        'impression' => 'Read only',
        'recommendation' => 'Read only',
    ], $radiographer);
    assertRadiology(($clinicalReadOnly['success'] ?? true) === false, 'Completed radiology request accepted an edit.');

    $directCreate = requireRadiologySuccess($radiologyService->createRequest([
        'visit_id' => $radiologyVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Direct',
        'priority' => 'Urgent',
        'study_requested' => 'Abdominal Ultrasound',
        'clinical_indication' => 'Direct radiology walk-in.',
    ], $radiographer), 'Direct radiology create');
    $directRequestId = (int)$directCreate['radiology_request_id'];
    $requestIds[] = $directRequestId;

    $directRequest = $radiologyService->getRequestById($directRequestId, $radiographer);
    assertRadiology($directRequest !== null && (string)$directRequest['request_source'] === 'Direct', 'Direct radiology request did not persist.');

    $noConsultation = $consultationService->getByVisit($radiologyVisitId);
    assertRadiology($noConsultation === null, 'Direct radiology patient unexpectedly required consultation.');

    $directReport = requireRadiologySuccess($radiologyService->saveResult([
        'radiology_request_id' => $directRequestId,
        'findings' => 'Liver is normal in size.',
        'impression' => 'Normal abdominal ultrasound.',
        'recommendation' => 'No additional radiology follow-up required.',
    ], $radiographer), 'Direct radiology report save');
    assertRadiology(($directReport['success'] ?? false) === true, 'Direct radiology report save failed.');
    requireRadiologySuccess($radiologyService->completeRequest($directRequestId, $radiographer), 'Direct radiology completion');

    $unauthorisedNurse = $radiologyService->createRequest([
        'visit_id' => $clinicalVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'priority' => 'Routine',
        'study_requested' => 'Should fail',
    ], $nurse);
    assertRadiology(($unauthorisedNurse['success'] ?? true) === false, 'Nurse created a radiology request unexpectedly.');

    $completedDenied = $radiologyService->createRequest([
        'visit_id' => $completedVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'priority' => 'Routine',
        'study_requested' => 'Completed encounter',
        'clinical_indication' => 'Should fail.',
    ], $doctor);
    assertRadiology(($completedDenied['success'] ?? true) === false, 'Completed encounter accepted a radiology request.');

    $completionWithoutReport = $radiologyService->createRequest([
        'visit_id' => $clinicalVisitId,
        'patient_id' => $patientId,
        'request_source' => 'Clinical',
        'priority' => 'Routine',
        'study_requested' => 'KUB',
        'clinical_indication' => 'Completion guard test.',
    ], $doctor);
    assertRadiology(($completionWithoutReport['success'] ?? false) === true, 'Guard request creation failed.');
    $guardRequestId = (int)$completionWithoutReport['radiology_request_id'];
    $requestIds[] = $guardRequestId;
    $guardComplete = $radiologyService->completeRequest($guardRequestId, $radiographer);
    assertRadiology(($guardComplete['success'] ?? true) === false, 'Radiology request completed without a report.');

    $auditCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM audit_logs
        WHERE visit_id IN ($clinicalVisitId, $radiologyVisitId)
          AND action IN (
              'RADIOLOGY_REQUEST_CREATED',
              'RADIOLOGY_REQUEST_STARTED',
              'RADIOLOGY_REPORT_CREATED',
              'RADIOLOGY_REPORT_UPDATED',
              'RADIOLOGY_REQUEST_COMPLETED',
              'RADIOLOGY_REQUEST_CANCELLED'
          )
    ")->fetchColumn();
    assertRadiology($auditCount >= 5, 'Radiology audit entries are missing.');

    $eventCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM encounter_events
        WHERE visit_id IN ($clinicalVisitId, $radiologyVisitId)
          AND event_type IN ('RADIOLOGY_REQUESTED', 'RADIOLOGY_REQUEST_STARTED', 'RADIOLOGY_COMPLETED')
    ")->fetchColumn();
    assertRadiology($eventCount >= 4, 'Radiology encounter events are missing.');

    assertRadiology(fileContains(__DIR__ . '/../modules/visits/partials/tabs/radiology.php', 'Request Radiology Study'), 'Workspace radiology tab is missing the request action.');
    assertRadiology(fileContains(__DIR__ . '/../modules/radiology/report.php', 'Findings'), 'Radiology report page is missing report fields.');
    assertRadiology(fileContains(__DIR__ . '/../modules/consultation/view.php', 'Radiology Requests'), 'Consultation view missing radiology summary.');
    assertRadiology(fileContains(__DIR__ . '/../modules/medical_records/chart.php', "'radiology'"), 'Patient chart missing radiology tab gate.');
    assertRadiology(fileContains(__DIR__ . '/../modules/medical_records/partials/chart_navigation.php', 'Radiology'), 'Patient chart navigation missing radiology tab.');

    $consultationRegression = $consultationService->getByVisit($clinicalVisitId);
    assertRadiology($consultationRegression === null || is_array($consultationRegression), 'Consultation regression check failed.');
    $vitalSignsRegression = $vitalSignsService->listByVisit($clinicalVisitId, $doctor);
    assertRadiology(is_array($vitalSignsRegression), 'Vital Signs regression check failed.');
    $nursingRegression = $nursingService->listByVisit($clinicalVisitId, $doctor);
    assertRadiology(is_array($nursingRegression), 'Nursing regression check failed.');

    echo 'PASS: Phase 3.5 Radiology CRUD, permissions, read models, and integration hooks.' . PHP_EOL;
} finally {
    if ($visitIds !== []) {
        $visitList = implode(',', array_map('intval', $visitIds));
        $pdo->exec("DELETE FROM encounter_events WHERE visit_id IN ($visitList)");
        $pdo->exec("DELETE FROM audit_logs WHERE visit_id IN ($visitList) AND action IN ('RADIOLOGY_REQUEST_CREATED','RADIOLOGY_REQUEST_STARTED','RADIOLOGY_REPORT_CREATED','RADIOLOGY_REPORT_UPDATED','RADIOLOGY_REQUEST_COMPLETED','RADIOLOGY_REQUEST_CANCELLED')");
    }

    if ($requestIds !== []) {
        $ids = implode(',', array_map('intval', $requestIds));
        $pdo->exec("DELETE FROM radiology_reports WHERE radiology_request_id IN ($ids)");
        $pdo->exec("DELETE FROM radiology_requests WHERE id IN ($ids)");
    }

    if ($visitIds !== []) {
        $pdo->exec("DELETE FROM visits WHERE id IN (" . implode(',', array_map('intval', $visitIds)) . ")");
    }
}
