<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/ConsultationService.php';
require_once __DIR__ . '/../services/MedicalRecordService.php';
require_once __DIR__ . '/../services/NursingService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/VitalSignsService.php';
require_once __DIR__ . '/../services/VisitService.php';

function assertNursing(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireNursingSuccess(array $result, string $operation): array
{
    assertNursing(
        ($result['success'] ?? false) === true,
        $operation . ': ' . implode(' ', $result['errors'] ?? [])
    );

    return $result;
}

function createNursingEncounter(PDO $pdo, array $actor, int $patientId, int $departmentId, string $status, string $suffix): int
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
        ':visit_number' => 'P33-' . $status . '-' . $suffix,
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
assertNursing(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Phase 3.3 tests are not isolated from the live database.'
);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL
    . 'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/024_phase3_nursing_up.sql', 24);

$pdo->exec("
    DELETE ce
    FROM encounter_events ce
    INNER JOIN visits v ON v.id = ce.visit_id
    WHERE v.visit_number LIKE 'P33-%'
");
$pdo->exec("
    DELETE al
    FROM audit_logs al
    INNER JOIN visits v ON v.id = al.visit_id
    WHERE v.visit_number LIKE 'P33-%'
");
$pdo->exec("
    DELETE na
    FROM nursing_assessments na
    INNER JOIN visits v ON v.id = na.visit_id
    WHERE v.visit_number LIKE 'P33-%'
");
$pdo->exec("
    DELETE vs
    FROM vital_signs vs
    INNER JOIN visits v ON v.id = vs.visit_id
    WHERE v.visit_number LIKE 'P33-%'
");
$pdo->exec("
    DELETE c
    FROM consultations c
    INNER JOIN visits v ON v.id = c.visit_id
    WHERE v.visit_number LIKE 'P33-%'
");
$pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'P33-%'");

$users = [];
$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('admin','dev_doctor','dev_nurse','dev_records')
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}

foreach (['admin', 'dev_doctor', 'dev_nurse', 'dev_records'] as $username) {
    assertNursing(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['admin'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];
$records = $users['dev_records'];

$patients = array_map(
    'intval',
    $pdo->query("
        SELECT id
        FROM patients
        WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002')
        ORDER BY hospital_number
    ")->fetchAll(PDO::FETCH_COLUMN)
);
assertNursing(count($patients) === 2, 'Dedicated patient fixtures are missing.');
[$patientId, $otherPatientId] = $patients;

$visitIds = [];
$assessmentIds = [];
$consultationIds = [];
$vitalSignsIds = [];

$doctorVisitId = null;
$nurseVisitId = null;
$closedVisitId = null;
$cancelledVisitId = null;
$adminVisitId = null;

try {
    foreach (['view_nursing', 'create_nursing', 'edit_nursing', 'complete_nursing'] as $permissionKey) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key AND is_active = 1');
        $stmt->execute([':permission_key' => $permissionKey]);
        assertNursing((int)$stmt->fetchColumn() === 1, 'Missing permission ' . $permissionKey . '.');
    }
    assertNursing(in_array('nursing_assessments', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Nursing table is missing.');

    $nurseVisitId = createNursingEncounter($pdo, $nurse, $patientId, (int)$nurse['department_id'], 'Nursing', 'NUR-' . time());
    $doctorVisitId = createNursingEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Doctor', 'DOC-' . time());
    $closedVisitId = createNursingEncounter($pdo, $nurse, $patientId, (int)$nurse['department_id'], 'Completed', 'CMP-' . time());
    $cancelledVisitId = createNursingEncounter($pdo, $nurse, $patientId, (int)$nurse['department_id'], 'Cancelled', 'CAN-' . time());
    $adminVisitId = createNursingEncounter($pdo, $admin, $patientId, (int)$admin['department_id'], 'Completed', 'ADM-' . time());
    $visitIds = [$nurseVisitId, $doctorVisitId, $closedVisitId, $cancelledVisitId, $adminVisitId];

    $nursingService = new NursingService($pdo, null, null, new PermissionService($pdo));
    $vitalSignsService = new VitalSignsService($pdo, null, new PermissionService($pdo));
    $consultationService = new ConsultationService($pdo);
    $visitService = new VisitService($pdo);
    $medicalRecordService = new MedicalRecordService($pdo);
    $patientService = new PatientService($pdo);

    $nurseCreate = requireNursingSuccess($nursingService->create([
        'visit_id' => $nurseVisitId,
        'patient_id' => $patientId,
        'general_condition' => 'Stable but tired.',
        'nursing_observation' => 'Patient alert and cooperative.',
        'pain_assessment' => 'Mild discomfort reported.',
        'mobility' => 'Independent ambulation.',
        'nutrition' => 'Ate breakfast.',
        'elimination' => 'No issues noted.',
        'skin_assessment' => 'Skin intact.',
        'fall_risk' => 'Low.',
        'nursing_interventions' => 'Routine monitoring provided.',
        'patient_response' => 'Tolerated assessment well.',
        'handover_notes' => 'Continue observation.',
        'additional_notes' => 'Baseline nursing assessment.',
    ], $nurse), 'Nurse create');
    $nurseAssessmentId = (int)$nurseCreate['nursing_assessment_id'];
    $assessmentIds[] = $nurseAssessmentId;

    $createdRow = $nursingService->getById($nurseAssessmentId, $nurse);
    assertNursing($createdRow !== null, 'Created nursing assessment was not returned.');
    assertNursing((int)($createdRow['nurse_id'] ?? 0) === (int)$nurse['id'], 'Nurse attribution is incorrect.');

    requireNursingSuccess($nursingService->update($nurseAssessmentId, [
        'general_condition' => 'Stable and improved.',
        'nursing_observation' => 'Patient resting comfortably.',
        'pain_assessment' => 'Pain reduced.',
        'mobility' => 'Walking unaided.',
        'nutrition' => 'Meal tolerated.',
        'elimination' => 'No concerns.',
        'skin_assessment' => 'No skin issues.',
        'fall_risk' => 'Low.',
        'nursing_interventions' => 'Observation continued.',
        'patient_response' => 'Responding well.',
        'handover_notes' => 'Continue current care plan.',
        'additional_notes' => 'Follow-up pending.',
    ], $nurse), 'Nurse update');

    requireNursingSuccess($nursingService->complete($nurseAssessmentId, $nurse), 'Nurse complete');
    assertNursing((string)$nursingService->getById($nurseAssessmentId, $nurse)['status'] === 'Completed', 'Completed nursing assessment not persisted.');

    $doctorView = $nursingService->getByVisit($nurseVisitId, $doctor);
    assertNursing($doctorView !== null, 'Doctor should be able to view nursing assessments.');

    $doctorCreate = $nursingService->create([
        'visit_id' => $nurseVisitId,
        'patient_id' => $patientId,
        'general_condition' => 'Should fail.',
    ], $doctor);
    assertNursing(($doctorCreate['success'] ?? true) === false, 'Doctor mutation was accepted.');

    $duplicateCreate = $nursingService->create([
        'visit_id' => $nurseVisitId,
        'patient_id' => $patientId,
        'general_condition' => 'Duplicate should fail.',
    ], $nurse);
    assertNursing(($duplicateCreate['success'] ?? true) === false, 'Duplicate nursing assessment was accepted.');

    $mismatch = $nursingService->create([
        'visit_id' => $nurseVisitId,
        'patient_id' => $otherPatientId,
        'general_condition' => 'Mismatch should fail.',
    ], $nurse);
    assertNursing(($mismatch['success'] ?? true) === false, 'Cross-patient encounter mismatch was accepted.');

    $closedCreate = $nursingService->create([
        'visit_id' => $closedVisitId,
        'patient_id' => $patientId,
        'general_condition' => 'Closed encounter should fail.',
    ], $nurse);
    assertNursing(($closedCreate['success'] ?? true) === false, 'Closed encounter accepted a nurse create.');

    $cancelledCreate = $nursingService->create([
        'visit_id' => $cancelledVisitId,
        'patient_id' => $patientId,
        'general_condition' => 'Cancelled encounter should fail.',
    ], $nurse);
    assertNursing(($cancelledCreate['success'] ?? true) === false, 'Cancelled encounter accepted a nurse create.');

    $adminClosedCreate = requireNursingSuccess($nursingService->create([
        'visit_id' => $adminVisitId,
        'patient_id' => $patientId,
        'general_condition' => 'Administrator development override.',
        'nursing_observation' => 'Admin test entry.',
    ], $admin), 'Administrator closed-encounter create');
    $adminAssessmentId = (int)$adminClosedCreate['nursing_assessment_id'];
    $assessmentIds[] = $adminAssessmentId;
    requireNursingSuccess($nursingService->update($adminAssessmentId, [
        'general_condition' => 'Administrator updated.',
        'nursing_observation' => 'Updated entry.',
    ], $admin), 'Administrator update');
    requireNursingSuccess($nursingService->complete($adminAssessmentId, $admin), 'Administrator complete');

    $readonlyUpdate = $nursingService->update($nurseAssessmentId, [
        'general_condition' => 'Should not update after completion.',
    ], $nurse);
    assertNursing(($readonlyUpdate['success'] ?? true) === false, 'Completed nursing assessment accepted an edit.');

    $readonlyComplete = $nursingService->complete($nurseAssessmentId, $nurse);
    assertNursing(($readonlyComplete['success'] ?? true) === false, 'Completed nursing assessment accepted duplicate completion.');

    $vitalSignsCreate = requireNursingSuccess($vitalSignsService->create([
        'visit_id' => $nurseVisitId,
        'patient_id' => $patientId,
        'temperature' => '36.9',
        'pulse' => '80',
        'respiratory_rate' => '16',
        'systolic_bp' => '118',
        'diastolic_bp' => '76',
        'oxygen_saturation' => '98',
        'weight' => '70',
        'height' => '175',
        'blood_glucose' => '5.1',
        'pain_score' => '2',
        'notes' => 'Contextual vitals for nursing.',
    ], $nurse), 'Vital signs create for nursing context');
    $vitalSignsIds[] = (int)$vitalSignsCreate['vital_signs_id'];

    $consultationCreate = requireNursingSuccess($consultationService->create([
        'visit_id' => $doctorVisitId,
        'presenting_complaint' => 'Nursing integration check',
        'history_of_presenting_complaint' => 'N/A',
        'examination_findings' => 'Stable',
        'assessment' => 'Stable',
        'diagnosis' => 'Test diagnosis',
        'treatment_plan' => 'Continue care',
        'advice' => '',
        'follow_up' => '',
        'referral_notes' => '',
    ], $admin), 'Consultation create for regression');
    $consultationIds[] = (int)$consultationCreate['consultation_id'];

    assertNursing(fileContains(__DIR__ . '/../modules/visits/workspace.php', "case 'nursing'"), 'Workspace missing nursing tab routing.');
    assertNursing(fileContains(__DIR__ . '/../modules/visits/partials/workspace_navigation.php', 'Nursing'), 'Workspace navigation missing nursing entry.');
    assertNursing(fileContains(__DIR__ . '/../modules/visits/partials/tabs/nursing.php', 'Start Nursing Assessment'), 'Workspace nursing tab missing action text.');
    assertNursing(fileContains(__DIR__ . '/../modules/medical_records/chart.php', "'nursing'"), 'Patient chart missing nursing tab gate.');
    assertNursing(fileContains(__DIR__ . '/../modules/medical_records/partials/chart_navigation.php', 'Nursing'), 'Patient chart navigation missing nursing tab.');
    assertNursing(fileContains(__DIR__ . '/../modules/medical_records/partials/nursing.php', 'No nursing assessment recorded.'), 'Patient chart nursing partial missing empty state.');
    assertNursing(fileContains(__DIR__ . '/../modules/nursing/create.php', 'Latest Vital Signs'), 'Nursing create page missing vitals integration.');
    assertNursing(fileContains(__DIR__ . '/../modules/nursing/create.php', 'Clinical Safety'), 'Nursing create page missing clinical safety integration.');
    assertNursing(fileContains(__DIR__ . '/../modules/nursing/create.php', 'Problem List'), 'Nursing create page missing problem list context.');
    assertNursing(fileContains(__DIR__ . '/../modules/nursing/create.php', 'Medical History'), 'Nursing create page missing medical history context.');
    assertNursing(fileContains(__DIR__ . '/../modules/nursing/save.php', 'requireCsrfToken'), 'Nursing save route missing CSRF protection.');
    assertNursing(fileContains(__DIR__ . '/../modules/nursing/update.php', 'requireCsrfToken'), 'Nursing update route missing CSRF protection.');
    assertNursing(fileContains(__DIR__ . '/../modules/nursing/complete.php', 'requireCsrfToken'), 'Nursing complete route missing CSRF protection.');
    assertNursing(fileContains(__DIR__ . '/../modules/nursing/_form.php', 'csrfField'), 'Nursing form missing CSRF field.');

    $auditCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM audit_logs
        WHERE visit_id IN ($nurseVisitId, $adminVisitId)
          AND action IN ('NURSING_ASSESSMENT_CREATED', 'NURSING_ASSESSMENT_UPDATED', 'NURSING_ASSESSMENT_COMPLETED')
    ")->fetchColumn();
    assertNursing($auditCount >= 5, 'Nursing audit entries are missing.');

    $eventCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM encounter_events
        WHERE visit_id IN ($nurseVisitId, $adminVisitId)
          AND event_type IN ('NURSING_ASSESSMENT_STARTED', 'NURSING_ASSESSMENT_COMPLETED')
    ")->fetchColumn();
    assertNursing($eventCount >= 4, 'Nursing encounter events are missing.');

    assertNursing(count($nursingService->listByVisit($nurseVisitId, $nurse)) >= 1, 'Nursing visit history is missing.');
    assertNursing(count($nursingService->listByPatient($patientId, $nurse)) >= 2, 'Nursing patient history did not include both encounters.');

    $chart = $medicalRecordService->getPatientChart($patientId, $nurse);
    assertNursing(($chart['success'] ?? false) === true, 'Patient chart could not load during nursing regression.');
    assertNursing($visitService->getVisitById($nurseVisitId) !== null, 'VisitService regression check failed.');

    echo 'PASS: Phase 3.3 Nursing CRUD, permissions, read models, and integration hooks.' . PHP_EOL;
} finally {
    $cleanupVisitIds = array_values(array_filter([
        $doctorVisitId,
        $nurseVisitId,
        $closedVisitId,
        $cancelledVisitId,
        $adminVisitId
    ], static fn ($value): bool => is_int($value) && $value > 0));

    if ($cleanupVisitIds !== []) {
        $visitList = implode(',', array_map('intval', $cleanupVisitIds));
        $pdo->exec("DELETE FROM encounter_events WHERE visit_id IN ($visitList)");
        $pdo->exec("DELETE FROM audit_logs WHERE visit_id IN ($visitList) AND action IN ('NURSING_ASSESSMENT_CREATED', 'NURSING_ASSESSMENT_UPDATED', 'NURSING_ASSESSMENT_COMPLETED')");
    }
    if ($consultationIds !== []) {
        $ids = implode(',', array_map('intval', $consultationIds));
        $pdo->exec("DELETE FROM encounter_events WHERE visit_id IN ($ids)");
        $pdo->exec("DELETE FROM audit_logs WHERE visit_id IN ($ids)");
        $pdo->exec("DELETE FROM consultations WHERE id IN ($ids)");
    }
    if ($vitalSignsIds !== []) {
        $ids = implode(',', array_map('intval', $vitalSignsIds));
        $pdo->exec("DELETE FROM vital_signs WHERE id IN ($ids)");
    }
    if ($assessmentIds !== []) {
        $ids = implode(',', array_map('intval', $assessmentIds));
        $pdo->exec("DELETE FROM nursing_assessments WHERE id IN ($ids)");
    }
    if ($visitIds !== []) {
        $pdo->exec("DELETE FROM visits WHERE id IN (" . implode(',', array_map('intval', $visitIds)) . ")");
    }
}
