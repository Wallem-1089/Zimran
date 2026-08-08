<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/ConsultationService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/VitalSignsService.php';
require_once __DIR__ . '/../services/VisitService.php';

function assertVital(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireVitalSuccess(array $result, string $operation): array
{
    assertVital(
        ($result['success'] ?? false) === true,
        $operation . ': ' . implode(' ', $result['errors'] ?? [])
    );

    return $result;
}

function approx(float $actual, float $expected, float $tolerance = 0.05): bool
{
    return abs($actual - $expected) <= $tolerance;
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
        ':visit_number' => 'P32-' . $status . '-' . $suffix,
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
assertVital(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Phase 3.2 tests are not isolated from the live database.'
);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL
    . 'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/023_phase3_vital_signs_up.sql', 23);

$pdo->exec("
    DELETE ce
    FROM encounter_events ce
    INNER JOIN visits v ON v.id = ce.visit_id
    WHERE v.visit_number LIKE 'P32-%'
");
$pdo->exec("
    DELETE al
    FROM audit_logs al
    INNER JOIN visits v ON v.id = al.visit_id
    WHERE v.visit_number LIKE 'P32-%'
");
$pdo->exec("
    DELETE vs
    FROM vital_signs vs
    INNER JOIN visits v ON v.id = vs.visit_id
    WHERE v.visit_number LIKE 'P32-%'
");
$pdo->exec("
    DELETE c
    FROM consultations c
    INNER JOIN visits v ON v.id = c.visit_id
    WHERE v.visit_number LIKE 'P32-%'
");
$pdo->exec("DELETE FROM visits WHERE visit_number LIKE 'P32-%'");

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
    assertVital(isset($users[$username]), 'Missing fixture user ' . $username . '.');
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
assertVital(count($patients) === 2, 'Dedicated patient fixtures are missing.');
[$patientId, $otherPatientId] = $patients;

$visitIds = [];
$consultationIds = [];
$vitalSignsIds = [];
$doctorVisitId = null;
$nurseVisitId = null;
$readonlyVisitId = null;
$cancelledVisitId = null;
$completedVisitId = null;

try {
    foreach (['view_vital_signs', 'create_vital_signs', 'edit_vital_signs'] as $permissionKey) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key AND is_active = 1');
        $stmt->execute([':permission_key' => $permissionKey]);
        assertVital((int)$stmt->fetchColumn() === 1, 'Missing permission ' . $permissionKey . '.');
    }
    assertVital(in_array('vital_signs', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Vital Signs table is missing.');

    $doctorVisitId = createEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Doctor', 'DOC-' . time());
    $nurseVisitId = createEncounter($pdo, $nurse, $patientId, (int)$nurse['department_id'], 'Nursing', 'NUR-' . time());
    $readonlyVisitId = createEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Doctor', 'RO-' . time());
    $cancelledVisitId = createEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Cancelled', 'CAN-' . time());
    $completedVisitId = createEncounter($pdo, $doctor, $patientId, (int)$doctor['department_id'], 'Completed', 'CMP-' . time());
    $visitIds = [$doctorVisitId, $nurseVisitId, $readonlyVisitId, $cancelledVisitId, $completedVisitId];

    $vitalSignsService = new VitalSignsService($pdo, null, new PermissionService($pdo));
    $visitService = new VisitService($pdo);
    $patientService = new PatientService($pdo);
    $consultationService = new ConsultationService($pdo);

    $doctorCreate = requireVitalSuccess($vitalSignsService->create([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'temperature' => '37.2',
        'pulse' => '84',
        'respiratory_rate' => '18',
        'systolic_bp' => '120',
        'diastolic_bp' => '80',
        'oxygen_saturation' => '97',
        'weight' => '70',
        'height' => '175',
        'blood_glucose' => '5.4',
        'pain_score' => '2',
        'notes' => 'Doctor baseline measurement.',
    ], $doctor), 'Doctor create');
    $doctorFirstId = (int)$doctorCreate['vital_signs_id'];
    $vitalSignsIds[] = $doctorFirstId;

    assertVital(approx((float)$vitalSignsService->getById($doctorFirstId, $doctor)['bmi'], 22.86), 'BMI calculation is incorrect.');

    $doctorSecond = requireVitalSuccess($vitalSignsService->create([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'temperature' => '37.8',
        'pulse' => '90',
        'respiratory_rate' => '20',
        'systolic_bp' => '118',
        'diastolic_bp' => '79',
        'oxygen_saturation' => '96',
        'weight' => '71',
        'height' => '175',
        'blood_glucose' => '5.9',
        'pain_score' => '3',
        'notes' => 'Second doctor measurement.',
    ], $doctor), 'Doctor create second record');
    $doctorSecondId = (int)$doctorSecond['vital_signs_id'];
    $vitalSignsIds[] = $doctorSecondId;

    $latestDoctor = $vitalSignsService->getLatestByVisit($doctorVisitId, $doctor);
    assertVital((int)($latestDoctor['id'] ?? 0) === $doctorSecondId, 'Latest record retrieval failed.');
    assertVital(count($vitalSignsService->listByVisit($doctorVisitId, $doctor)) === 2, 'Visit history count is incorrect.');
    assertVital(count($vitalSignsService->listByPatient($patientId, $doctor)) >= 2, 'Patient history did not include visit records.');

    requireVitalSuccess($vitalSignsService->update($doctorSecondId, [
        'temperature' => '38.0',
        'pulse' => '92',
        'respiratory_rate' => '19',
        'systolic_bp' => '116',
        'diastolic_bp' => '78',
        'oxygen_saturation' => '95',
        'weight' => '71',
        'height' => '175',
        'blood_glucose' => '6.0',
        'pain_score' => '4',
        'notes' => 'Doctor update.',
    ], $doctor), 'Doctor update');

    $adminCreate = requireVitalSuccess($vitalSignsService->create([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'temperature' => '36.9',
        'pulse' => '82',
        'respiratory_rate' => '17',
        'systolic_bp' => '119',
        'diastolic_bp' => '79',
        'oxygen_saturation' => '98',
        'weight' => '71',
        'height' => '175',
        'blood_glucose' => '5.5',
        'pain_score' => '1',
        'notes' => 'Administrator test entry.',
    ], $admin), 'Administrator create');
    $adminVitalId = (int)$adminCreate['vital_signs_id'];
    $vitalSignsIds[] = $adminVitalId;
    assertVital((int)$vitalSignsService->getLatestByVisit($doctorVisitId, $admin)['id'] === $adminVitalId, 'Administrator latest retrieval failed.');

    $nurseCreate = requireVitalSuccess($vitalSignsService->create([
        'visit_id' => $nurseVisitId,
        'patient_id' => $patientId,
        'temperature' => '36.7',
        'pulse' => '78',
        'respiratory_rate' => '16',
        'systolic_bp' => '112',
        'diastolic_bp' => '74',
        'oxygen_saturation' => '99',
        'weight' => '68',
        'height' => '170',
        'blood_glucose' => '5.1',
        'pain_score' => '1',
        'notes' => 'Nurse baseline measurement.',
    ], $nurse), 'Nurse create');
    $nurseVitalId = (int)$nurseCreate['vital_signs_id'];
    $vitalSignsIds[] = $nurseVitalId;

    requireVitalSuccess($vitalSignsService->update($nurseVitalId, [
        'temperature' => '36.8',
        'pulse' => '80',
        'respiratory_rate' => '16',
        'systolic_bp' => '114',
        'diastolic_bp' => '76',
        'oxygen_saturation' => '98',
        'weight' => '68',
        'height' => '170',
        'blood_glucose' => '5.0',
        'pain_score' => '0',
        'notes' => 'Nurse update.',
    ], $nurse), 'Nurse update');

    $consultation = requireVitalSuccess($consultationService->create([
        'visit_id' => $doctorVisitId,
        'presenting_complaint' => 'Follow-up review',
        'history_of_presenting_complaint' => 'Symptoms improving.',
        'examination_findings' => 'Stable.',
        'assessment' => 'Improving.',
        'diagnosis' => 'Routine review',
        'treatment_plan' => 'Continue care',
        'advice' => 'Return if worse',
        'follow_up' => 'One week',
        'referral_notes' => '',
    ], $doctor), 'Consultation create');
    $consultationIds[] = (int)$consultation['consultation_id'];

    $recordsView = $vitalSignsService->getLatestByVisit($doctorVisitId, $records);
    assertVital($recordsView !== null, 'Records Officer should be able to view vital signs when permitted.');
    $recordsCreate = $vitalSignsService->create([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'temperature' => '36.6',
        'pulse' => '76',
        'respiratory_rate' => '16',
        'systolic_bp' => '110',
        'diastolic_bp' => '70',
        'oxygen_saturation' => '97',
        'weight' => '69',
        'height' => '172',
        'blood_glucose' => '5.2',
        'pain_score' => '1',
        'notes' => 'Should be denied.',
    ], $records);
    assertVital(($recordsCreate['success'] ?? true) === false, 'Unauthorized mutation was accepted.');

    $mismatch = $vitalSignsService->create([
        'visit_id' => $doctorVisitId,
        'patient_id' => $otherPatientId,
        'temperature' => '36.6',
        'pulse' => '76',
        'notes' => 'Mismatch should fail.',
    ], $doctor);
    assertVital(($mismatch['success'] ?? true) === false, 'Cross-patient encounter mismatch was accepted.');

    $invalid = $vitalSignsService->create([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'temperature' => '10',
        'pulse' => '0',
        'notes' => 'Invalid values.',
    ], $doctor);
    assertVital(($invalid['success'] ?? true) === false, 'Invalid ranges were accepted.');

    $completedCreate = $vitalSignsService->create([
        'visit_id' => $completedVisitId,
        'patient_id' => $patientId,
        'temperature' => '36.5',
        'pulse' => '74',
        'notes' => 'Closed encounter should reject.',
    ], $doctor);
    assertVital(($completedCreate['success'] ?? true) === false, 'Completed encounter accepted a create.');

    $cancelledCreate = $vitalSignsService->create([
        'visit_id' => $cancelledVisitId,
        'patient_id' => $patientId,
        'temperature' => '36.4',
        'pulse' => '75',
        'notes' => 'Cancelled encounter should reject.',
    ], $doctor);
    assertVital(($cancelledCreate['success'] ?? true) === false, 'Cancelled encounter accepted a create.');

    $pdo->prepare("UPDATE visits SET visit_status = 'Completed' WHERE id = :id")->execute([':id' => $readonlyVisitId]);
    $readonlyCreate = $vitalSignsService->create([
        'visit_id' => $readonlyVisitId,
        'patient_id' => $patientId,
        'temperature' => '36.5',
        'pulse' => '77',
        'notes' => 'Should not be possible after completion.',
    ], $doctor);
    assertVital(($readonlyCreate['success'] ?? true) === false, 'Completed encounter accepted a late create.');
    $readonlyRecord = requireVitalSuccess($vitalSignsService->create([
        'visit_id' => $doctorVisitId,
        'patient_id' => $patientId,
        'temperature' => '37.1',
        'pulse' => '88',
        'notes' => 'Mutation target.',
    ], $doctor), 'Doctor create for read-only update target');
    $readonlyId = (int)$readonlyRecord['vital_signs_id'];
    $vitalSignsIds[] = $readonlyId;
    $pdo->prepare("UPDATE visits SET visit_status = 'Completed' WHERE id = :id")->execute([':id' => $doctorVisitId]);
    $readonlyUpdate = $vitalSignsService->update($readonlyId, [
        'temperature' => '38.1',
        'pulse' => '91',
        'notes' => 'Should not update completed encounter.',
    ], $doctor);
    assertVital(($readonlyUpdate['success'] ?? true) === false, 'Completed encounter accepted an edit.');

    assertVital(fileContains(__DIR__ . '/../modules/visits/workspace.php', "case 'vitals'"), 'Workspace tab integration is missing.');
    assertVital(fileContains(__DIR__ . '/../modules/visits/partials/workspace_navigation.php', 'Vital Signs'), 'Workspace navigation missing Vital Signs.');
    assertVital(fileContains(__DIR__ . '/../modules/visits/partials/tabs/vitals.php', 'Record Vital Signs'), 'Workspace vitals tab missing action text.');
    assertVital(fileContains(__DIR__ . '/../modules/consultation/view.php', 'Latest Vital Signs'), 'Consultation view missing latest-vitals read model.');
    assertVital(fileContains(__DIR__ . '/../modules/consultation/create.php', 'Latest Vital Signs'), 'Consultation create missing latest-vitals read model.');
    assertVital(fileContains(__DIR__ . '/../modules/medical_records/chart.php', "'vitals'"), 'Patient chart missing vitals tab gate.');
    assertVital(fileContains(__DIR__ . '/../modules/medical_records/partials/chart_navigation.php', 'Vital Signs'), 'Patient chart navigation missing Vital Signs.');
    assertVital(fileContains(__DIR__ . '/../modules/medical_records/partials/vital_signs.php', 'No vital signs recorded.'), 'Patient chart vital-signs partial missing empty state.');

    $auditCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM audit_logs
        WHERE visit_id IN ($doctorVisitId, $nurseVisitId, $readonlyVisitId)
          AND action IN ('VITAL_SIGNS_CREATED', 'VITAL_SIGNS_UPDATED')
    ")->fetchColumn();
    assertVital($auditCount >= 5, 'Vital Signs audit entries are missing.');

    echo 'PASS: Phase 3.2 Vital Signs CRUD, permissions, read models, and integration hooks.' . PHP_EOL;
} finally {
    $cleanupVisitIds = array_values(array_filter([
        $doctorVisitId,
        $nurseVisitId,
        $readonlyVisitId
    ], static fn ($value): bool => is_int($value) && $value > 0));

    if ($cleanupVisitIds !== []) {
        $visitList = implode(',', array_map('intval', $cleanupVisitIds));
        $pdo->exec("DELETE FROM encounter_events WHERE visit_id IN ($visitList)");
        $pdo->exec("DELETE FROM audit_logs WHERE visit_id IN ($visitList) AND action IN ('VITAL_SIGNS_CREATED', 'VITAL_SIGNS_UPDATED')");
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
    if ($visitIds !== []) {
        $pdo->exec("DELETE FROM visits WHERE id IN (" . implode(',', array_map('intval', $visitIds)) . ")");
    }
}
