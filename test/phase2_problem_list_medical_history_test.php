<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/ProblemListService.php';
require_once __DIR__ . '/../services/SettingsService.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';

function assertLongitudinal(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireLongitudinalSuccess(array $result, string $operation): array
{
    assertLongitudinal(
        ($result['success'] ?? false) === true,
        $operation . ': ' . implode(' ', $result['errors'] ?? [])
    );
    return $result;
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertLongitudinal(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Longitudinal tests are not isolated from the live database.'
);

$users = [];
$rows = $pdo->query("SELECT u.*,r.role_name,d.department_name FROM users u INNER JOIN roles r ON r.id=u.role_id INNER JOIN departments d ON d.id=u.department_id WHERE u.username IN ('admin','dev_doctor','dev_records','dev_nurse')")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) { $users[$row['username']] = $row; }
foreach (['admin', 'dev_doctor', 'dev_records', 'dev_nurse'] as $username) {
    assertLongitudinal(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}
$admin = $users['admin']; $doctor = $users['dev_doctor']; $records = $users['dev_records'];
$patientIds = array_map('intval', $pdo->query("SELECT id FROM patients WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002') ORDER BY hospital_number")->fetchAll(PDO::FETCH_COLUMN));
assertLongitudinal(count($patientIds) === 2, 'Dedicated patient fixtures are missing.');
[$patientId, $otherPatientId] = $patientIds;

$suffix = date('YmdHis') . random_int(1000, 9999);
$pdo->prepare("INSERT INTO visits (visit_number,patient_id,visit_date,visit_type,current_department_id,attending_doctor_id,current_department_received_status,visit_status,created_by) VALUES (:number,:patient,NOW(),'Outpatient',:department,:doctor,'Received','Doctor',:creator)")->execute([
    ':number' => 'TEST-LONG-' . $suffix, ':patient' => $patientId,
    ':department' => $doctor['department_id'], ':doctor' => $doctor['id'],
    ':creator' => $admin['id']
]);
$visitId = (int)$pdo->lastInsertId();
$problemIds = []; $historyIds = [];
$service = new ProblemListService($pdo);
$settings = new SettingsService($pdo);

try {
    $ledger = $pdo->prepare("SELECT COUNT(*) FROM schema_migrations WHERE migration_name='019_phase2_problem_list_medical_history_up.sql'");
    $ledger->execute();
    assertLongitudinal((int)$ledger->fetchColumn() === 1, 'Migration 019 is not ledger-recorded.');

    $invalidSetting = $settings->update('problem_list.categories', ['Chronic Condition', 'Unsupported'], (int)$admin['id']);
    assertLongitudinal(!($invalidSetting['success'] ?? true), 'Schema-invalid problem category setting was accepted.');

    $added = requireLongitudinalSuccess($service->addProblem([
        'patient_id' => $patientId, 'source_visit_id' => $visitId,
        'problem_name' => 'Test Hypertension ' . $suffix,
        'category' => 'Chronic Condition', 'severity' => 'Moderate',
        'onset_date' => '2024-01-01', 'reason' => 'Focused test creation.'
    ], (int)$admin['id'], (int)$admin['department_id']), 'Add problem');
    $problemId = (int)$added['problem_id']; $problemIds[] = $problemId;
    $problem = $service->getProblemById($problemId);
    assertLongitudinal($problem !== null && (int)$problem['version'] === 1, 'Initial problem version is invalid.');

    $duplicate = $service->addProblem([
        'patient_id' => $patientId, 'problem_name' => '  TEST HYPERTENSION ' . $suffix,
        'category' => 'Chronic Condition', 'severity' => 'Unknown', 'reason' => 'Duplicate check.'
    ], (int)$admin['id']);
    assertLongitudinal(!($duplicate['success'] ?? true), 'Duplicate active problem was accepted.');

    $selfVerify = $service->verifyProblem($problemId, 'Self check.', (int)$admin['id'], 1, $visitId);
    assertLongitudinal(!($selfVerify['success'] ?? true), 'Problem self-verification was accepted.');
    requireLongitudinalSuccess($service->verifyProblem($problemId, 'Independent clinical verification.', (int)$doctor['id'], 1, $visitId, (int)$doctor['department_id']), 'Verify problem');
    $verified = $service->getProblemById($problemId);
    assertLongitudinal($verified['verification_status'] === 'Confirmed', 'Problem was not confirmed.');

    $updated = requireLongitudinalSuccess($service->updateProblem($problemId, [
        'severity' => 'Severe', 'reason' => 'Material severity correction.', 'visit_id' => $visitId
    ], 2, (int)$admin['id'], (int)$admin['department_id']), 'Update problem');
    assertLongitudinal(($updated['verification_reset'] ?? false) === true, 'Material update did not reset verification.');
    $stale = $service->updateProblem($problemId, ['notes' => 'Stale', 'reason' => 'Stale test.'], 2, (int)$admin['id']);
    assertLongitudinal(!($stale['success'] ?? true) && !empty($stale['conflict']), 'Stale problem update was not rejected.');

    requireLongitudinalSuccess($service->deactivateProblem($problemId, 'Temporarily inactive.', (int)$doctor['id'], 3, $visitId), 'Deactivate problem');
    requireLongitudinalSuccess($service->reactivateProblem($problemId, 'Condition active again.', (int)$doctor['id'], 4, $visitId), 'Reactivate problem');
    requireLongitudinalSuccess($service->resolveProblem($problemId, 'Condition resolved.', (int)$doctor['id'], 5, $visitId, '2026-01-01'), 'Resolve problem');
    $invalidTransition = $service->verifyProblem($problemId, 'Invalid.', (int)$doctor['id'], 6, $visitId);
    assertLongitudinal(!($invalidTransition['success'] ?? true), 'Resolved problem accepted an invalid transition.');
    requireLongitudinalSuccess($service->reactivateProblem($problemId, 'Recurrence.', (int)$doctor['id'], 6, $visitId), 'Reactivate resolved problem');

    $entered = requireLongitudinalSuccess($service->addProblem([
        'patient_id' => $patientId, 'problem_name' => 'Erroneous problem ' . $suffix,
        'category' => 'Other', 'severity' => 'Unknown', 'reason' => 'Error lifecycle fixture.'
    ], (int)$admin['id']), 'Add error problem');
    $errorProblemId = (int)$entered['problem_id']; $problemIds[] = $errorProblemId;
    requireLongitudinalSuccess($service->markProblemEnteredInError($errorProblemId, 'Recorded against wrong chart.', (int)$doctor['id'], 1), 'Mark problem entered in error');
    assertLongitudinal(!($service->reactivateProblem($errorProblemId, 'Invalid.', (int)$doctor['id'], 2)['success'] ?? true), 'Entered-in-error problem was reactivated.');

    $problemHistory = requireLongitudinalSuccess($service->getProblemHistoryForUser($problemId, $admin), 'Read problem history');
    assertLongitudinal(count($problemHistory['data']['history']) >= 7, 'Problem append-only history is incomplete.');

    $historyAdded = requireLongitudinalSuccess($service->addHistoryEntry([
        'patient_id' => $patientId, 'source_visit_id' => $visitId,
        'history_type' => 'Surgical History', 'title' => 'Appendectomy ' . $suffix,
        'description' => 'Historical procedure.', 'event_date' => '2020-06-01',
        'date_precision' => 'Exact', 'status' => 'Historical',
        'confidentiality_level' => 'Confidential', 'reason' => 'History fixture.'
    ], (int)$admin['id'], (int)$admin['department_id']), 'Add medical history');
    $historyId = (int)$historyAdded['history_entry_id']; $historyIds[] = $historyId;
    assertLongitudinal(!($service->verifyHistoryEntry($historyId, 'Self.', (int)$admin['id'], 1)['success'] ?? true), 'Medical-history self-verification was accepted.');
    requireLongitudinalSuccess($service->verifyHistoryEntry($historyId, 'Independent verification.', (int)$doctor['id'], 1, $visitId), 'Verify medical history');
    $masked = $service->getHistoryEntryByIdForUser($historyId, $records, false);
    assertLongitudinal(($masked['success'] ?? false) && !empty($masked['data']['entry']['confidential_hidden']), 'Confidential medical history was not masked.');
    $summaryMasked = $service->getPatientMedicalHistoryForUser($patientId, $admin, false);
    $summaryRecord = array_values(array_filter($summaryMasked, static fn (array $row): bool => (int)$row['id'] === $historyId))[0] ?? [];
    assertLongitudinal(!empty($summaryRecord['confidential_hidden']), 'Confidential summary returned full details.');
    $auditBefore = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE patient_id=$patientId AND action='CONFIDENTIAL_MEDICAL_HISTORY_VIEWED'")->fetchColumn();
    requireLongitudinalSuccess($service->getHistoryEntryByIdForUser($historyId, $admin, true), 'Read confidential history detail');
    $auditAfter = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE patient_id=$patientId AND action='CONFIDENTIAL_MEDICAL_HISTORY_VIEWED'")->fetchColumn();
    assertLongitudinal($auditAfter === $auditBefore + 1, 'Confidential detail access was not audited exactly once.');
    $correction = requireLongitudinalSuccess($service->correctHistoryEntry($historyId, [
        'description' => 'Corrected historical procedure details.', 'reason' => 'Source correction.', 'visit_id' => $visitId
    ], 2, (int)$admin['id']), 'Correct medical history');
    assertLongitudinal(!empty($correction['verification_reset']), 'Medical-history correction did not reset verification.');
    assertLongitudinal(!($service->updateHistoryEntry($historyId, ['description' => 'Stale', 'reason' => 'Stale.'], 2, (int)$admin['id'])['success'] ?? true), 'Stale medical-history update was accepted.');
    requireLongitudinalSuccess($service->markHistoryEnteredInError($historyId, 'Historical source disproved.', (int)$doctor['id'], 3, $visitId), 'Mark history entered in error');
    $versions = requireLongitudinalSuccess($service->getMedicalHistoryVersionsForUser($historyId, $admin), 'Read medical-history versions');
    assertLongitudinal(count($versions['data']['versions']) === 4, 'Medical-history version count is incorrect.');

    $crossVisit = $service->addProblem([
        'patient_id' => $otherPatientId, 'source_visit_id' => $visitId,
        'problem_name' => 'Cross patient ' . $suffix, 'category' => 'Other',
        'severity' => 'Unknown', 'reason' => 'Cross-patient rejection.'
    ], (int)$admin['id']);
    assertLongitudinal(!($crossVisit['success'] ?? true), 'Cross-patient encounter linkage was accepted.');

    assertLongitudinal((int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE visit_id=$visitId AND event_type IN ('PROBLEM_ADDED','PROBLEM_VERIFIED','PROBLEM_RESOLVED','PROBLEM_REACTIVATED','MEDICAL_HISTORY_RECORDED','MEDICAL_HISTORY_CORRECTED')")->fetchColumn() >= 6, 'Expected encounter-context events are missing.');
    assertLongitudinal((int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE patient_id=$patientId AND action IN ('PROBLEM_ADDED','PROBLEM_UPDATED','PROBLEM_VERIFIED','MEDICAL_HISTORY_ADDED','MEDICAL_HISTORY_CORRECTED')")->fetchColumn() >= 5, 'Expected longitudinal audit events are missing.');

    $failingAudit = new class($pdo) extends AuditService {
        public function logPatient(?int $userId, int $patientId, ?int $visitId, string $module, string $action, string $description, ?int $departmentId = null, string $severity = 'INFO', ?string $eventType = null): bool { return false; }
    };
    $rollbackService = new ProblemListService($pdo, $failingAudit);
    $rollbackName = 'Rollback problem ' . $suffix;
    $rolledBack = $rollbackService->addProblem(['patient_id' => $patientId, 'problem_name' => $rollbackName, 'category' => 'Other', 'severity' => 'Unknown', 'reason' => 'Rollback test.'], (int)$admin['id']);
    assertLongitudinal(!($rolledBack['success'] ?? true), 'Audit failure did not fail the mutation.');
    $check = $pdo->prepare('SELECT COUNT(*) FROM patient_problems WHERE problem_name=:name'); $check->execute([':name' => $rollbackName]);
    assertLongitudinal((int)$check->fetchColumn() === 0, 'Audit failure did not roll back the problem record.');

    assertLongitudinal(!in_array('encounter_diagnoses', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Encounter diagnosis table was introduced.');
    echo "Phase 2.4 Problem List and Medical History tests passed.\n";
} finally {
    if ($problemIds !== []) { $ids = implode(',', array_map('intval', $problemIds)); $pdo->exec("DELETE FROM patient_problem_history WHERE problem_id IN ($ids)"); $pdo->exec("DELETE FROM patient_problems WHERE id IN ($ids)"); }
    if ($historyIds !== []) { $ids = implode(',', array_map('intval', $historyIds)); $pdo->exec("DELETE FROM patient_medical_history_versions WHERE history_entry_id IN ($ids)"); $pdo->exec("DELETE FROM patient_medical_history WHERE id IN ($ids)"); }
    $pdo->exec("DELETE FROM encounter_events WHERE visit_id=$visitId");
    $pdo->exec("DELETE FROM audit_logs WHERE visit_id=$visitId OR (patient_id=$patientId AND action LIKE 'PROBLEM_%') OR (patient_id=$patientId AND action LIKE 'MEDICAL_HISTORY_%')");
    $pdo->exec("DELETE FROM visits WHERE id=$visitId");
}
