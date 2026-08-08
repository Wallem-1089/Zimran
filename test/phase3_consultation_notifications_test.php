<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/ConsultationService.php';
require_once __DIR__ . '/../services/DepartmentNotificationService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/VisitService.php';

function assertPhase31(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requirePhase31Success(array $result, string $operation): array
{
    assertPhase31(($result['success'] ?? false) === true, $operation . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertPhase31($databaseName === $resolved['test'] && $databaseName !== $resolved['live'], 'Phase 3.1 tests are not isolated from live.');
fwrite(STDOUT, 'Resolved live database: ' . $resolved['live'] . PHP_EOL . 'Resolved test database: ' . $resolved['test'] . PHP_EOL);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/022_phase3_consultation_notifications_up.sql', 22);

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
    assertPhase31(isset($users[$username]), 'Missing fixture ' . $username . '.');
}
$admin = $users['admin'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];
$records = $users['dev_records'];

$patientIds = array_map(
    'intval',
    $pdo->query("SELECT id FROM patients WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002') ORDER BY hospital_number")->fetchAll(PDO::FETCH_COLUMN)
);
assertPhase31(count($patientIds) === 2, 'Dedicated patients are missing.');
[$patientId, $otherPatientId] = $patientIds;

$suffix = date('YmdHis') . random_int(1000, 9999);
$visitIds = [];
$consultationIds = [];
$notificationIds = [];

$createVisit = function (string $status, int $departmentId, ?int $doctorId, int $patient) use ($pdo, $admin, $suffix, &$visitIds): int {
    $stmt = $pdo->prepare("
        INSERT INTO visits (
            visit_number, patient_id, visit_date, visit_type, current_department_id,
            attending_doctor_id, current_department_received_status, visit_status, created_by
        ) VALUES (
            :number, :patient, NOW(), 'Outpatient', :department,
            :doctor, 'Received', :status, :creator
        )
    ");
    $stmt->execute([
        ':number' => 'T31-' . substr($status, 0, 1) . '-' . $suffix . '-' . count($visitIds),
        ':patient' => $patient,
        ':department' => $departmentId,
        ':doctor' => $doctorId,
        ':status' => $status,
        ':creator' => (int)$admin['id'],
    ]);
    $visitId = (int)$pdo->lastInsertId();
    $visitIds[] = $visitId;
    return $visitId;
};

$doctorVisitId = $createVisit('Doctor', (int)$doctor['department_id'], (int)$doctor['id'], $patientId);
$completedVisitId = $createVisit('Completed', (int)$doctor['department_id'], (int)$doctor['id'], $patientId);

$consultationService = new ConsultationService($pdo);
$notificationService = new DepartmentNotificationService($pdo);
$patientService = new PatientService($pdo);
$visitService = new VisitService($pdo);

$consultationData = [
    'visit_id' => $doctorVisitId,
    'presenting_complaint' => 'Headache and fever',
    'history_of_presenting_complaint' => 'Two days of symptoms.',
    'examination_findings' => 'Stable, febrile.',
    'assessment' => 'Likely viral illness.',
    'diagnosis' => 'Acute febrile illness',
    'treatment_plan' => 'Hydration and review.',
    'advice' => 'Return if worse.',
    'follow_up' => 'Two days.',
    'referral_notes' => '',
];

try {
    foreach (['consultations', 'department_notifications'] as $table) {
        assertPhase31(in_array($table, $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Missing table ' . $table . '.');
    }
    foreach (['view_consultation', 'create_consultation', 'edit_consultation', 'complete_consultation'] as $permission) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = :permission AND is_active = 1');
        $stmt->execute([':permission' => $permission]);
        assertPhase31((int)$stmt->fetchColumn() === 1, 'Missing permission ' . $permission . '.');
    }

    $searchResults = $patientService->searchPatients(['hospital_number' => 'DEV-PATIENT-0001']);
    assertPhase31(count($searchResults) >= 1, 'Authenticated patient search returned no fixture patient.');
    $activeVisit = $visitService->getActiveVisit($patientId);
    assertPhase31($activeVisit !== null && (int)$activeVisit['patient_id'] === $patientId, 'Active encounter discovery failed.');

    $created = requirePhase31Success($consultationService->create($consultationData, $doctor), 'Doctor create consultation');
    $consultationId = (int)$created['consultation_id'];
    $consultationIds[] = $consultationId;
    $consultation = $consultationService->getById($consultationId);
    assertPhase31((int)$consultation['doctor_id'] === (int)$doctor['id'], 'Clinical doctor attribution is wrong.');
    assertPhase31((int)$consultation['created_by'] === (int)$doctor['id'], 'Creator attribution is wrong.');

    $duplicate = $consultationService->create($consultationData, $doctor);
    assertPhase31(!($duplicate['success'] ?? true), 'Duplicate consultation per visit was accepted.');

    $unauthorized = $consultationService->update($consultationId, $consultationData + ['assessment' => 'Nurse overwrite'], $nurse);
    assertPhase31(!($unauthorized['success'] ?? true), 'Unauthorized consultation mutation succeeded.');

    requirePhase31Success(
        $consultationService->update($consultationId, $consultationData + ['assessment' => 'Improving clinically.'], $admin),
        'Administrator update draft'
    );
    $updated = $consultationService->getById($consultationId);
    assertPhase31((int)$updated['doctor_id'] === (int)$doctor['id'], 'Administrator update changed clinical doctor.');
    assertPhase31((int)$updated['updated_by'] === (int)$admin['id'], 'Administrator update actor was not recorded.');

    requirePhase31Success($consultationService->complete($consultationId, $admin), 'Administrator complete consultation');
    $completed = $consultationService->getById($consultationId);
    assertPhase31((string)$completed['status'] === 'Completed', 'Consultation was not completed.');
    assertPhase31((int)$completed['doctor_id'] === (int)$doctor['id'], 'Administrator completion changed clinical doctor.');
    assertPhase31((int)$completed['completed_by'] === (int)$admin['id'], 'Administrator completion actor was not recorded.');
    assertPhase31(!($consultationService->update($consultationId, $consultationData, $doctor)['success'] ?? true), 'Completed consultation remained editable.');

    $blocked = $consultationService->create($consultationData + ['visit_id' => $completedVisitId], $doctor);
    assertPhase31(!($blocked['success'] ?? true), 'Completed encounter accepted consultation creation.');

    assertPhase31((int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE visit_id = $doctorVisitId AND action IN ('CONSULTATION_CREATED','CONSULTATION_UPDATED','CONSULTATION_COMPLETED')")->fetchColumn() === 3, 'Consultation audit count is incorrect.');
    assertPhase31((int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE visit_id = $doctorVisitId AND event_type IN ('CONSULTATION_STARTED','CONSULTATION_COMPLETED')")->fetchColumn() === 2, 'Consultation encounter events are incorrect.');

    $beforeVisit = $visitService->getVisitById($doctorVisitId);
    $sent = requirePhase31Success(
        $notificationService->send([
            'visit_id' => $doctorVisitId,
            'to_department_id' => (int)$records['department_id'],
            'reason' => 'Please review patient file.',
        ], $admin),
        'Send department notification'
    );
    $notificationId = (int)$sent['notification_id'];
    $notificationIds[] = $notificationId;
    $afterVisit = $visitService->getVisitById($doctorVisitId);
    assertPhase31((int)$beforeVisit['current_department_id'] === (int)$afterVisit['current_department_id'], 'Notification changed encounter department.');
    assertPhase31((string)$beforeVisit['visit_status'] === (string)$afterVisit['visit_status'], 'Notification changed encounter status.');
    assertPhase31($notificationService->getUnreadCount((int)$records['department_id']) >= 1, 'Unread notification count did not increase.');
    assertPhase31(count($notificationService->listForDepartment((int)$records['department_id'], 'Unread')) >= 1, 'Notification inbox did not show unread item.');

    requirePhase31Success($notificationService->markRead($notificationId, $records), 'Mark notification read');
    $read = $notificationService->getById($notificationId);
    assertPhase31(in_array((string)$read['status'], ['Read', 'Resolved'], true), 'Notification did not mark read.');
    requirePhase31Success($notificationService->resolve($notificationId, $records), 'Resolve notification');
    $resolvedNotification = $notificationService->getById($notificationId);
    assertPhase31((string)$resolvedNotification['status'] === 'Resolved', 'Notification was not resolved.');
    assertPhase31(!($notificationService->send(['visit_id' => $completedVisitId, 'to_department_id' => (int)$records['department_id'], 'reason' => 'Closed'], $admin)['success'] ?? true), 'Completed encounter accepted notification.');

    assertPhase31((int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE visit_id = $doctorVisitId AND event_type = 'DEPARTMENT_NOTIFICATION_SENT'")->fetchColumn() === 1, 'Notification sent event missing or duplicated.');
    assertPhase31((int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE visit_id = $doctorVisitId AND event_type IN ('DEPARTMENT_NOTIFICATION_READ','DEPARTMENT_NOTIFICATION_RESOLVED')")->fetchColumn() === 0, 'Read/resolve polluted the encounter timeline.');
    assertPhase31((int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE visit_id = $doctorVisitId AND action IN ('DEPARTMENT_NOTIFICATION_SENT','DEPARTMENT_NOTIFICATION_READ','DEPARTMENT_NOTIFICATION_RESOLVED')")->fetchColumn() === 3, 'Notification audit count is incorrect.');

    echo "Phase 3.1 Consultation and Department Notifications tests passed.\n";
} finally {
    if ($notificationIds !== []) {
        $pdo->exec('DELETE FROM department_notifications WHERE id IN (' . implode(',', array_map('intval', $notificationIds)) . ')');
    }
    if ($consultationIds !== []) {
        $pdo->exec('DELETE FROM consultations WHERE id IN (' . implode(',', array_map('intval', $consultationIds)) . ')');
    }
    if ($visitIds !== []) {
        $ids = implode(',', array_map('intval', $visitIds));
        $pdo->exec("DELETE FROM encounter_events WHERE visit_id IN ($ids)");
        $pdo->exec("DELETE FROM audit_logs WHERE visit_id IN ($ids)");
        $pdo->exec("DELETE FROM visits WHERE id IN ($ids)");
    }
}
