<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../services/ClinicalSafetyService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';

function assertClinicalSafety(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function clinicalSafetySuccess(array $result, string $operation): array
{
    assertClinicalSafety(
        ($result['success'] ?? false) === true,
        $operation . ' failed: ' . implode(' ', $result['errors'] ?? [])
    );

    return $result;
}

$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
$appConfig = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($appConfig);
assertClinicalSafety(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Clinical Safety tests are not isolated from the live database.'
);

$users = [];
$userRows = $pdo->query('
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id=u.role_id
    INNER JOIN departments d ON d.id=u.department_id
    WHERE u.username IN (\'walter\',\'dev_doctor\',\'dev_nurse\',\'dev_accounts\')
')->fetchAll(PDO::FETCH_ASSOC);
foreach ($userRows as $userRow) {
    $users[(string)$userRow['username']] = $userRow;
}
foreach (['walter', 'dev_doctor', 'dev_nurse', 'dev_accounts'] as $username) {
    assertClinicalSafety(isset($users[$username]), 'Missing fixture user: ' . $username);
}

$admin = $users['walter'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];
$accounts = $users['dev_accounts'];
$actorId = (int)$admin['id'];
$patientService = new PatientService($pdo);
$service = new ClinicalSafetyService($pdo);
$permissions = new PermissionService($pdo);
$patientId = null;
$otherPatientId = null;
$visitId = null;
$suffix = date('YmdHis') . random_int(1000, 9999);

try {
    foreach (['patient_allergies', 'patient_allergy_history', 'patient_alerts', 'patient_alert_history'] as $table) {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM information_schema.TABLES
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table_name
        ');
        $stmt->execute([':table_name' => $table]);
        assertClinicalSafety((int)$stmt->fetchColumn() === 1, $table . ' is missing.');
    }

    foreach ([
        'view_clinical_safety', 'record_allergies', 'update_allergies',
        'verify_allergies', 'resolve_allergies', 'manage_clinical_alerts',
        'view_confidential_alerts', 'view_clinical_safety_history'
    ] as $permissionKey) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key=:permission_key');
        $stmt->execute([':permission_key' => $permissionKey]);
        assertClinicalSafety((int)$stmt->fetchColumn() === 1, $permissionKey . ' is missing.');
    }

    $patientData = [
        'first_name' => 'Safety' . $suffix,
        'middle_name' => 'Clinical',
        'last_name' => 'Verification',
        'gender' => 'Unknown',
        'date_of_birth' => '1980-01-01',
        'marital_status' => 'Single',
        'occupation' => 'Dedicated test fixture',
        'phone' => '082' . substr($suffix, -8),
        'email' => '',
        'address' => 'Dedicated test database only',
        'state_of_origin' => 'Test',
        'nationality' => 'Test',
        'blood_group' => 'O+',
        'genotype' => 'AA',
        'allergies' => 'Legacy penicillin note - unverified',
        'next_of_kin' => 'Safety Test Kin',
        'next_of_kin_relationship' => 'Sibling',
        'next_of_kin_phone' => '08000000000',
        'duplicate_review_ack' => '1'
    ];
    $createdPatient = clinicalSafetySuccess(
        $patientService->createPatient($patientData, $actorId),
        'Clinical Safety patient creation'
    );
    $patientId = (int)$createdPatient['patient_id'];

    $otherData = $patientData;
    $otherData['first_name'] = 'Other' . $suffix;
    $otherData['phone'] = '083' . substr($suffix, -8);
    $otherData['allergies'] = '';
    $createdOther = clinicalSafetySuccess(
        $patientService->createPatient($otherData, $actorId),
        'Cross-patient fixture creation'
    );
    $otherPatientId = (int)$createdOther['patient_id'];

    $doctorDepartment = (int)$pdo->query(
        "SELECT id FROM departments WHERE department_name='Doctor' LIMIT 1"
    )->fetchColumn();
    $visitNumber = 'SAFETY-' . $suffix;
    $visit = $pdo->prepare('
        INSERT INTO visits (
            visit_number,patient_id,visit_date,visit_type,current_department_id,
            current_department_received_status,current_department_received_by,
            current_department_received_at,visit_status,created_by
        ) VALUES (
            :visit_number,:patient_id,NOW(),\'Outpatient\',:department_id,
            \'Received\',:received_by,NOW(),\'Doctor\',:created_by
        )
    ');
    $visit->execute([
        ':visit_number' => $visitNumber,
        ':patient_id' => $patientId,
        ':department_id' => $doctorDepartment,
        ':received_by' => $actorId,
        ':created_by' => $actorId
    ]);
    $visitId = (int)$pdo->lastInsertId();

    assertClinicalSafety($permissions->canViewClinicalSafety($patientId, $admin), 'Administrator safety override failed.');
    assertClinicalSafety($permissions->canRecordAllergies($patientId, $doctor), 'Doctor allergy permission failed.');
    assertClinicalSafety(!$permissions->canVerifyAllergies($patientId, $nurse), 'Nurse verification policy was not enforced.');
    assertClinicalSafety(!$permissions->canViewClinicalSafety($patientId, $accounts), 'Accounts received clinical safety access.');

    $allergyResult = clinicalSafetySuccess($service->recordAllergy([
        'patient_id' => $patientId,
        'source_visit_id' => $visitId,
        'allergy_type' => 'Drug',
        'substance' => 'Penicillin',
        'reaction' => 'Anaphylaxis',
        'severity' => 'Life-threatening',
        'onset_date' => '2020-01-02',
        'reason' => 'Patient-reported safety history.'
    ], $actorId), 'Allergy creation');
    $allergyId = (int)$allergyResult['allergy_id'];

    $invalidAllergy = $service->recordAllergy([
        'patient_id' => $patientId,
        'allergy_type' => 'Unsupported',
        'substance' => '',
        'severity' => 'Extreme',
        'reason' => ''
    ], $actorId);
    assertClinicalSafety(($invalidAllergy['success'] ?? true) === false, 'Invalid allergy was accepted.');

    $duplicateAllergy = $service->recordAllergy([
        'patient_id' => $patientId,
        'allergy_type' => 'Drug',
        'substance' => ' penicillin ',
        'severity' => 'Severe',
        'reason' => 'Duplicate test.'
    ], $actorId);
    assertClinicalSafety(($duplicateAllergy['success'] ?? true) === false, 'Duplicate active allergy was accepted.');

    $wrongVisit = $service->recordAllergy([
        'patient_id' => $otherPatientId,
        'source_visit_id' => $visitId,
        'allergy_type' => 'Food',
        'substance' => 'Peanut',
        'severity' => 'Moderate',
        'reason' => 'Cross-patient visit test.'
    ], $actorId);
    assertClinicalSafety(($wrongVisit['success'] ?? true) === false, 'Cross-patient encounter linkage was accepted.');

    $currentAllergy = $service->getAllergyById($allergyId);
    $updatedAllergy = clinicalSafetySuccess($service->updateAllergy(
        $allergyId,
        ['reaction' => 'Anaphylaxis and wheeze', 'reason' => 'Reaction clarified.'],
        (int)$currentAllergy['version'],
        $actorId
    ), 'Allergy update');
    $staleAllergy = $service->updateAllergy(
        $allergyId,
        ['reaction' => 'Stale overwrite', 'reason' => 'Stale test.'],
        (int)$currentAllergy['version'],
        $actorId
    );
    assertClinicalSafety(($staleAllergy['conflict'] ?? false) === true, 'Stale allergy update was not rejected.');

    clinicalSafetySuccess($service->verifyAllergy(
        $allergyId,
        'Clinically confirmed.',
        (int)$doctor['id'],
        (int)$updatedAllergy['version'],
        $visitId
    ), 'Allergy verification');
    $verified = $service->getAllergyById($allergyId);
    clinicalSafetySuccess($service->resolveAllergy(
        $allergyId,
        'No longer clinically active.',
        $actorId,
        (int)$verified['version'],
        $visitId
    ), 'Allergy resolution');
    assertClinicalSafety(count($service->getAllergyHistory($allergyId)) === 4, 'Allergy history is incomplete.');

    $errorAllergy = clinicalSafetySuccess($service->recordAllergy([
        'patient_id' => $patientId,
        'allergy_type' => 'Food',
        'substance' => 'Test Food',
        'severity' => 'Unknown',
        'reason' => 'Entered-in-error transition fixture.'
    ], $actorId), 'Entered-in-error fixture');
    $errorRow = $service->getAllergyById((int)$errorAllergy['allergy_id']);
    clinicalSafetySuccess($service->markAllergyEnteredInError(
        (int)$errorAllergy['allergy_id'],
        'Incorrect patient report.',
        $actorId,
        (int)$errorRow['version']
    ), 'Allergy entered-in-error transition');

    $alertResult = clinicalSafetySuccess($service->createAlert([
        'patient_id' => $patientId,
        'visit_id' => $visitId,
        'alert_type' => 'Clinical Risk',
        'title' => 'Airway Risk',
        'reason' => 'Restricted airway management information.',
        'priority' => 'Critical',
        'confidentiality_level' => 'Confidential',
        'change_reason' => 'Initial clinical risk assessment.'
    ], $actorId), 'Clinical alert creation');
    $alertId = (int)$alertResult['alert_id'];
    $masked = $service->getAlertById($alertId, false);
    assertClinicalSafety(
        ($masked['confidential_hidden'] ?? false) === true
            && $masked['reason'] === null
            && $masked['title'] === 'Confidential clinical safety alert',
        'Confidential alert masking failed.'
    );

    $currentAlert = $service->getAlertById($alertId, true);
    $updatedAlert = clinicalSafetySuccess($service->updateAlert(
        $alertId,
        [
            'priority' => 'High',
            'reason' => 'Restricted airway plan updated.',
            'change_reason' => 'Clinical review completed.'
        ],
        (int)$currentAlert['version'],
        $actorId
    ), 'Clinical alert update');
    $staleAlert = $service->updateAlert(
        $alertId,
        ['priority' => 'Low', 'reason' => 'Stale', 'change_reason' => 'Stale test.'],
        (int)$currentAlert['version'],
        $actorId
    );
    assertClinicalSafety(($staleAlert['conflict'] ?? false) === true, 'Stale alert update was not rejected.');

    clinicalSafetySuccess($service->closeAlert(
        $alertId,
        'Risk episode closed.',
        $actorId,
        (int)$updatedAlert['version'],
        $visitId
    ), 'Clinical alert closure');
    $closedAlert = $service->getAlertById($alertId, true);
    clinicalSafetySuccess($service->reactivateAlert(
        $alertId,
        'Risk recurred.',
        $actorId,
        (int)$closedAlert['version'],
        $visitId
    ), 'Clinical alert reactivation');
    assertClinicalSafety(count($service->getAlertHistory($alertId)) === 4, 'Alert history is incomplete.');

    $expiredAlert = clinicalSafetySuccess($service->createAlert([
        'patient_id' => $patientId,
        'alert_type' => 'Fall Risk',
        'title' => 'Expired test alert',
        'reason' => 'Expired banner filtering fixture.',
        'priority' => 'Low',
        'confidentiality_level' => 'Standard',
        'starts_at' => '2020-01-01 00:00',
        'expires_at' => '2020-01-02 00:00',
        'change_reason' => 'Expiry verification.'
    ], $actorId), 'Expired alert creation');
    assertClinicalSafety((int)$expiredAlert['alert_id'] > 0, 'Expired alert fixture was not created.');

    $banner = clinicalSafetySuccess($service->getSafetyBanner($patientId, false), 'Safety banner');
    $items = $banner['data']['items'];
    assertClinicalSafety(($items[0]['kind'] ?? '') === 'alert', 'Safety banner priority ordering failed.');
    assertClinicalSafety(($items[0]['confidential_hidden'] ?? false) === true, 'Banner exposed confidential details.');
    assertClinicalSafety(
        $banner['data']['legacy_allergy_text'] === 'Legacy penicillin note - unverified',
        'Legacy allergy compatibility text is missing.'
    );
    assertClinicalSafety(
        count(array_filter($items, static fn (array $item): bool => ($item['title'] ?? '') === 'Expired test alert')) === 0,
        'Expired alert appeared in the active safety banner.'
    );
    $legacyStored = $pdo->prepare('SELECT allergies FROM patients WHERE id=:id');
    $legacyStored->execute([':id' => $patientId]);
    assertClinicalSafety(
        $legacyStored->fetchColumn() === 'Legacy penicillin note - unverified',
        'Legacy allergy text was modified.'
    );

    $safetyBanner = $banner;
    $safetyBannerUrl = 'chart.php?patient=' . $patientId . '&tab=safety';
    ob_start();
    require __DIR__ . '/../modules/medical_records/partials/clinical_safety_banner.php';
    $chartBannerHtml = (string)ob_get_clean();
    ob_start();
    require __DIR__ . '/../modules/medical_records/partials/clinical_safety_banner.php';
    $workspaceBannerHtml = (string)ob_get_clean();
    assertClinicalSafety(
        $chartBannerHtml === $workspaceBannerHtml
            && str_contains($chartBannerHtml, 'Confidential clinical safety alert')
            && str_contains($chartBannerHtml, 'Legacy unstructured allergy information'),
        'Shared safety banner rendering is inconsistent or incomplete.'
    );
    $chartSource = (string)file_get_contents(
        __DIR__ . '/../modules/medical_records/chart.php'
    );
    $workspaceSource = (string)file_get_contents(
        __DIR__ . '/../modules/visits/workspace.php'
    );
    assertClinicalSafety(
        str_contains($chartSource, "partials/clinical_safety_banner.php")
            && str_contains($workspaceSource, "medical_records/partials/clinical_safety_banner.php"),
        'Patient Chart and Encounter Workspace do not share the banner partial.'
    );

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    assertClinicalSafety(verifyCsrfToken($_SESSION['csrf_token']), 'Valid CSRF token was rejected.');
    assertClinicalSafety(!verifyCsrfToken('invalid-token'), 'Invalid CSRF token was accepted.');

    foreach ([
        'ALLERGY_RECORDED' => 2,
        'ALLERGY_UPDATED' => 1,
        'ALLERGY_VERIFIED' => 1,
        'ALLERGY_RESOLVED' => 1,
        'ALLERGY_ENTERED_IN_ERROR' => 1,
        'CLINICAL_ALERT_CREATED' => 2,
        'CLINICAL_ALERT_UPDATED' => 1,
        'CLINICAL_ALERT_CLOSED' => 1,
        'CLINICAL_ALERT_REACTIVATED' => 1
    ] as $eventType => $expectedCount) {
        $audit = $pdo->prepare('SELECT COUNT(*) FROM audit_logs WHERE patient_id=:patient_id AND action=:action');
        $audit->execute([':patient_id' => $patientId, ':action' => $eventType]);
        assertClinicalSafety((int)$audit->fetchColumn() === $expectedCount, $eventType . ' audit count is incorrect.');
    }
    $eventCount = $pdo->prepare('
        SELECT COUNT(*) FROM encounter_events
        WHERE visit_id=:visit_id AND event_type IN (
            \'ALLERGY_RECORDED\',\'ALLERGY_VERIFIED\',\'ALLERGY_RESOLVED\',
            \'CLINICAL_ALERT_CREATED\',\'CLINICAL_ALERT_CLOSED\',\'CLINICAL_ALERT_REACTIVATED\'
        )
    ');
    $eventCount->execute([':visit_id' => $visitId]);
    assertClinicalSafety((int)$eventCount->fetchColumn() === 6, 'Encounter event boundary is incorrect.');

    $failingAudit = new class ($pdo) extends AuditService {
        public function logPatient(
            ?int $userId,
            int $patientId,
            ?int $visitId,
            string $module,
            string $action,
            string $description,
            ?int $departmentId = null,
            string $severity = 'INFO',
            ?string $eventType = null
        ): bool {
            return false;
        }
    };
    $rollbackService = new ClinicalSafetyService($pdo, $failingAudit);
    $rolledBack = $rollbackService->recordAllergy([
        'patient_id' => $patientId,
        'allergy_type' => 'Environmental',
        'substance' => 'Rollback Dust',
        'severity' => 'Mild',
        'reason' => 'Audit rollback verification.'
    ], $actorId);
    assertClinicalSafety(($rolledBack['success'] ?? true) === false, 'Audit failure did not fail the allergy write.');
    $rollbackCount = $pdo->prepare('SELECT COUNT(*) FROM patient_allergies WHERE patient_id=:patient_id AND substance=\'Rollback Dust\'');
    $rollbackCount->execute([':patient_id' => $patientId]);
    assertClinicalSafety((int)$rollbackCount->fetchColumn() === 0, 'Audit failure did not roll back allergy data.');

    $failingEvent = new class ($pdo) extends EncounterEventService {
        public function record(
            int $visitId,
            string $eventType,
            string $eventTitle,
            ?string $eventDescription,
            ?int $departmentId,
            ?int $performedBy
        ): array {
            return ['success' => false, 'errors' => ['Forced event failure.']];
        }
    };
    $eventRollbackService = new ClinicalSafetyService($pdo, null, $failingEvent);
    $eventRolledBack = $eventRollbackService->createAlert([
        'patient_id' => $patientId,
        'visit_id' => $visitId,
        'alert_type' => 'Other',
        'title' => 'Rollback Event Alert',
        'reason' => 'Encounter event rollback verification.',
        'priority' => 'Medium',
        'confidentiality_level' => 'Standard',
        'change_reason' => 'Event rollback verification.'
    ], $actorId);
    assertClinicalSafety(($eventRolledBack['success'] ?? true) === false, 'Encounter event failure did not fail the alert write.');
    $eventRollbackCount = $pdo->prepare('SELECT COUNT(*) FROM patient_alerts WHERE patient_id=:patient_id AND title=\'Rollback Event Alert\'');
    $eventRollbackCount->execute([':patient_id' => $patientId]);
    assertClinicalSafety((int)$eventRollbackCount->fetchColumn() === 0, 'Encounter event failure did not roll back alert data.');

    $downSql = (string)file_get_contents(__DIR__ . '/../database/migrations/017_phase2_clinical_safety_down.sql');
    $upSql = (string)file_get_contents(__DIR__ . '/../database/migrations/017_phase2_clinical_safety_up.sql');
    DatabaseSafety::assertSafeSchema($downSql, $resolved['live']);
    DatabaseSafety::assertSafeSchema($upSql, $resolved['live']);
    assertClinicalSafety(
        str_contains($downSql, 'DROP TABLE patient_allergies')
            && str_contains($upSql, 'CREATE TABLE patient_allergies'),
        'Migration 017 paired-file safety review failed.'
    );

    echo "Phase 2 Milestone 2.3 Clinical Safety tests passed on {$databaseName}." . PHP_EOL;
} finally {
    foreach (array_filter([$patientId, $otherPatientId]) as $clinicalPatientId) {
        $pdo->prepare('DELETE FROM patient_allergy_history WHERE patient_id=:patient_id')->execute([':patient_id' => $clinicalPatientId]);
        $pdo->prepare('DELETE FROM patient_alert_history WHERE patient_id=:patient_id')->execute([':patient_id' => $clinicalPatientId]);
        $pdo->prepare('DELETE FROM patient_allergies WHERE patient_id=:patient_id')->execute([':patient_id' => $clinicalPatientId]);
        $pdo->prepare('DELETE FROM patient_alerts WHERE patient_id=:patient_id')->execute([':patient_id' => $clinicalPatientId]);
    }
    if ($visitId !== null) {
        $pdo->prepare('DELETE FROM encounter_events WHERE visit_id=:visit_id')->execute([':visit_id' => $visitId]);
        $pdo->prepare('DELETE FROM visits WHERE id=:visit_id')->execute([':visit_id' => $visitId]);
    }
    foreach (array_filter([$patientId, $otherPatientId]) as $fixturePatientId) {
        $pdo->prepare('DELETE FROM audit_logs WHERE patient_id=:patient_id')->execute([':patient_id' => $fixturePatientId]);
        $pdo->prepare('DELETE FROM patient_duplicate_candidates WHERE patient_id_low=:patient_id_low OR patient_id_high=:patient_id_high')->execute([
            ':patient_id_low' => $fixturePatientId,
            ':patient_id_high' => $fixturePatientId
        ]);
        $pdo->prepare('DELETE FROM patient_identifier_history WHERE patient_id=:patient_id')->execute([':patient_id' => $fixturePatientId]);
        $pdo->prepare('DELETE FROM patient_identifiers WHERE patient_id=:patient_id')->execute([':patient_id' => $fixturePatientId]);
        $pdo->prepare('DELETE FROM patient_demographic_history WHERE patient_id=:patient_id')->execute([':patient_id' => $fixturePatientId]);
        $pdo->prepare('DELETE FROM record_access_logs WHERE patient_id=:patient_id')->execute([':patient_id' => $fixturePatientId]);
        $pdo->prepare('DELETE FROM patients WHERE id=:patient_id')->execute([':patient_id' => $fixturePatientId]);
    }
}
