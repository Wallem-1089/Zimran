<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/ClinicalSafetyService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/SettingsService.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';

function assertSafetyHardening(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireSafetySuccess(array $result, string $operation): array
{
    assertSafetyHardening(
        ($result['success'] ?? false) === true,
        $operation . ': ' . implode(' ', $result['errors'] ?? [])
    );
    return $result;
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertSafetyHardening(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Hardening tests are not isolated from the live database.'
);

$users = [];
$rows = $pdo->query("
    SELECT u.*,r.role_name,d.department_name
    FROM users u
    INNER JOIN roles r ON r.id=u.role_id
    INNER JOIN departments d ON d.id=u.department_id
    WHERE u.username IN ('walter','dev_doctor','dev_records','dev_nurse')
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}
foreach (['walter', 'dev_doctor', 'dev_records', 'dev_nurse'] as $username) {
    assertSafetyHardening(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$patientId = (int)$pdo->query(
    "SELECT id FROM patients WHERE hospital_number='DEV-PATIENT-0001' LIMIT 1"
)->fetchColumn();
assertSafetyHardening($patientId > 0, 'Dedicated fixture patient is missing.');

$admin = $users['walter'];
$doctor = $users['dev_doctor'];
$records = $users['dev_records'];
$settings = new SettingsService($pdo);
$permissions = new PermissionService($pdo, $settings);
$service = new ClinicalSafetyService($pdo, null, null, $settings, $permissions);
$allergyIds = [];
$alertIds = [];
$suffix = date('YmdHis') . random_int(1000, 9999);

try {
    $ledger = $pdo->prepare(
        "SELECT COUNT(*) FROM schema_migrations WHERE migration_name='018_phase2_clinical_safety_hardening_up.sql'"
    );
    $ledger->execute();
    assertSafetyHardening((int)$ledger->fetchColumn() === 1, 'Migration 018 is not recorded.');

    $unsupported = $settings->update(
        'clinical_safety.allergy_types',
        ['Drug', 'UnsupportedSchemaValue'],
        (int)$admin['id']
    );
    assertSafetyHardening(!($unsupported['success'] ?? true), 'Schema-invalid setting was accepted.');
    requireSafetySuccess(
        $settings->update(
            'clinical_safety.allergy_types',
            ['Drug', 'Food', 'Environmental', 'Biological', 'Other'],
            (int)$admin['id']
        ),
        'Restore allergy types'
    );

    $recorded = requireSafetySuccess($service->recordAllergy([
        'patient_id' => $patientId,
        'allergy_type' => 'Drug',
        'substance' => 'Hardening Drug ' . $suffix,
        'reaction' => 'Initial reaction',
        'severity' => 'Severe',
        'reason' => 'Hardening verification fixture.'
    ], (int)$admin['id']), 'Record allergy');
    $allergyId = (int)$recorded['allergy_id'];
    $allergyIds[] = $allergyId;

    $selfVerification = $service->verifyAllergy(
        $allergyId,
        'Self-verification must fail.',
        (int)$admin['id'],
        1
    );
    assertSafetyHardening(!($selfVerification['success'] ?? true), 'Self-verification was accepted.');

    $verified = requireSafetySuccess($service->verifyAllergy(
        $allergyId,
        'Independent clinical verification.',
        (int)$doctor['id'],
        1
    ), 'Independent allergy verification');
    $updated = requireSafetySuccess($service->updateAllergy(
        $allergyId,
        [
            'severity' => 'Life-threatening',
            'reason' => 'Clinically significant severity correction.'
        ],
        (int)$verified['version'],
        (int)$admin['id']
    ), 'Material allergy update');
    $updatedRow = $service->getAllergyById($allergyId);
    assertSafetyHardening(
        ($updatedRow['verification_status'] ?? '') === 'Unverified'
            && $updatedRow['verified_by'] === null,
        'Material edit did not reset allergy verification.'
    );

    $deactivated = requireSafetySuccess($service->deactivateAllergy(
        $allergyId,
        'Temporarily inactive during clinical review.',
        (int)$admin['id'],
        (int)$updated['version']
    ), 'Deactivate allergy');
    $reactivated = requireSafetySuccess($service->reactivateAllergy(
        $allergyId,
        'Clinical review restored the active record.',
        (int)$admin['id'],
        (int)$deactivated['version']
    ), 'Reactivate allergy');
    assertSafetyHardening((int)$reactivated['version'] === 5, 'Allergy lifecycle versioning is incorrect.');

    $alertResult = requireSafetySuccess($service->createAlert([
        'patient_id' => $patientId,
        'alert_type' => 'Clinical Risk',
        'title' => 'Restricted hardening alert ' . $suffix,
        'reason' => 'Restricted details for authorization verification.',
        'priority' => 'Critical',
        'confidentiality_level' => 'Confidential',
        'change_reason' => 'Confidential hardening fixture.'
    ], (int)$admin['id']), 'Create confidential alert');
    $alertId = (int)$alertResult['alert_id'];
    $alertIds[] = $alertId;

    $compatibilityLookup = $service->getAlertById($alertId, true);
    assertSafetyHardening(
        !empty($compatibilityLookup['confidential_hidden'])
            && $compatibilityLookup['reason'] === null,
        'Compatibility lookup exposed confidential content.'
    );
    $authorizedLookup = requireSafetySuccess(
        $service->getAlertByIdForUser($alertId, $admin),
        'Authorized confidential lookup'
    );
    assertSafetyHardening(
        empty($authorizedLookup['data']['alert']['confidential_hidden'])
            && $authorizedLookup['data']['alert']['reason'] !== null,
        'Authorized confidential lookup did not return protected details.'
    );

    $historyForRecords = requireSafetySuccess(
        $service->getAlertHistoryForUser($alertId, $records),
        'Masked confidential history lookup'
    );
    assertSafetyHardening(
        !empty($historyForRecords['data']['history'][0]['confidential_hidden'])
            && $historyForRecords['data']['history'][0]['new_snapshot'] === null,
        'Per-version confidential history was exposed.'
    );

    $expired = requireSafetySuccess($service->createAlert([
        'patient_id' => $patientId,
        'alert_type' => 'Other',
        'title' => 'Expired hardening alert ' . $suffix,
        'reason' => 'Dynamic expiry fixture.',
        'priority' => 'Low',
        'confidentiality_level' => 'Standard',
        'starts_at' => '2020-01-01 00:00',
        'expires_at' => '2020-01-02 00:00',
        'change_reason' => 'Expiry fixture.'
    ], (int)$admin['id']), 'Create expired alert');
    $expiredId = (int)$expired['alert_id'];
    $alertIds[] = $expiredId;
    $activeAlerts = $service->getPatientAlertsForUser($patientId, $admin, false);
    assertSafetyHardening(
        !in_array($expiredId, array_map('intval', array_column($activeAlerts, 'id')), true),
        'Dynamically expired alert appeared in the active list.'
    );
    $allAlerts = $service->getPatientAlertsForUser($patientId, $admin, true);
    $expiredRows = array_values(array_filter(
        $allAlerts,
        static fn (array $row): bool => (int)$row['id'] === $expiredId
    ));
    assertSafetyHardening(
        ($expiredRows[0]['effective_status'] ?? '') === 'Expired',
        'Expired alert does not have the Expired effective status.'
    );

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
    $failClosedService = new ClinicalSafetyService(
        $pdo,
        $failingAudit,
        null,
        $settings,
        $permissions
    );
    $failedView = $failClosedService->getSafetyBannerForUser($patientId, $admin);
    assertSafetyHardening(
        !($failedView['success'] ?? true) && !empty($failedView['audit_failed']),
        'Required Clinical Safety access logging did not fail closed.'
    );

    foreach ([
        'allergy_save.php', 'allergy_update.php', 'allergy_verify.php',
        'allergy_deactivate.php', 'allergy_reactivate.php', 'alert_save.php',
        'alert_update.php', 'alert_close.php', 'alert_reactivate.php'
    ] as $controller) {
        $source = (string)file_get_contents(
            __DIR__ . '/../modules/medical_records/safety/' . $controller
        );
        assertSafetyHardening(
            str_contains($source, 'requireCsrfToken()'),
            $controller . ' is missing CSRF enforcement.'
        );
    }

    $migrationDown = (string)file_get_contents(
        __DIR__ . '/../database/migrations/018_phase2_clinical_safety_hardening_down.sql'
    );
    $migrationUp = (string)file_get_contents(
        __DIR__ . '/../database/migrations/018_phase2_clinical_safety_hardening_up.sql'
    );
    DatabaseSafety::assertSafeSchema($migrationDown, $resolved['live']);
    DatabaseSafety::assertSafeSchema($migrationUp, $resolved['live']);
    $pdo->exec($migrationDown);
    assertSafetyHardening(
        !(bool)$pdo->query("SELECT COUNT(*) FROM system_settings WHERE setting_key='clinical_safety.allow_self_allergy_verification'")->fetchColumn(),
        'Migration 018 down verification failed.'
    );
    $pdo->exec($migrationUp);
    $restoredRules = (string)$pdo->query(
        "SELECT validation_rules FROM system_settings WHERE setting_key='clinical_safety.allergy_types'"
    )->fetchColumn();
    assertSafetyHardening(
        str_contains($restoredRules, 'schema_values'),
        'Migration 018 up verification failed.'
    );

    echo 'Phase 2 Milestone 2.3.1 Clinical Safety hardening tests passed on '
        . $databaseName . '.' . PHP_EOL;
} finally {
    foreach ($allergyIds as $allergyId) {
        $pdo->prepare('DELETE FROM patient_allergy_history WHERE allergy_id=:id')->execute([':id' => $allergyId]);
        $pdo->prepare('DELETE FROM patient_allergies WHERE id=:id')->execute([':id' => $allergyId]);
    }
    foreach ($alertIds as $alertId) {
        $pdo->prepare('DELETE FROM patient_alert_history WHERE alert_id=:id')->execute([':id' => $alertId]);
        $pdo->prepare('DELETE FROM patient_alerts WHERE id=:id')->execute([':id' => $alertId]);
    }
    $pdo->prepare("DELETE FROM audit_logs WHERE patient_id=:patient_id AND module='Medical Records'")->execute([':patient_id' => $patientId]);
}
