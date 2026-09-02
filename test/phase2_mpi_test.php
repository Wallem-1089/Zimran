<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/PatientIdentifierService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';

function assertMpi(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireMpiSuccess(array $result, string $operation): array
{
    assertMpi(
        ($result['success'] ?? false) === true,
        $operation . ' failed: ' . implode(' ', $result['errors'] ?? [])
    );
    return $result;
}

function mpiPatient(string $suffix, string $phone, bool $acknowledged = false): array
{
    return [
        'first_name' => 'Mpi' . $suffix,
        'middle_name' => 'Exact',
        'last_name' => 'Verification',
        'gender' => 'Unknown',
        'date_of_birth' => '1988-08-08',
        'marital_status' => 'Single',
        'occupation' => 'Automated test fixture',
        'phone' => $phone,
        'email' => strtolower($suffix) . '@mpi.test.invalid',
        'address' => 'Dedicated test database only',
        'state_of_origin' => 'Test',
        'nationality' => 'Test',
        'blood_group' => 'O+',
        'genotype' => 'AA',
        'allergies' => '',
        'next_of_kin' => 'MPI Test Kin',
        'next_of_kin_relationship' => 'Sibling',
        'next_of_kin_phone' => '08000000000',
        'duplicate_review_ack' => $acknowledged ? '1' : ''
    ];
}

$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
$appConfig = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($appConfig);
assertMpi($databaseName === $resolved['test'], 'MPI tests are not connected to the test database.');
assertMpi($databaseName !== $resolved['live'], 'MPI tests resolved to the live database.');

$admin = $pdo->query('
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username = \'walter\'
    ORDER BY u.id LIMIT 1
')->fetch(PDO::FETCH_ASSOC);
assertMpi((bool)$admin, 'The deterministic super administrator fixture is missing.');
$actorId = (int)$admin['id'];

$patientService = new PatientService($pdo);
$identifierService = new PatientIdentifierService($pdo);
$permissionService = new PermissionService($pdo);
$patientIds = [];
$identifierIds = [];
$suffix = date('YmdHis') . (string)random_int(1000, 9999);

try {
    foreach ([
        'patient_identifiers',
        'patient_identifier_history',
        'patient_duplicate_candidates'
    ] as $table) {
        $exists = $pdo->prepare('
            SELECT COUNT(*) FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name
        ');
        $exists->execute([':table_name' => $table]);
        assertMpi((int)$exists->fetchColumn() === 1, $table . ' is missing.');
    }

    foreach ([
        'manage_patient_identifiers',
        'view_duplicate_candidates',
        'review_duplicate_candidates'
    ] as $permissionKey) {
        $permission = $pdo->prepare(
            'SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key'
        );
        $permission->execute([':permission_key' => $permissionKey]);
        assertMpi((int)$permission->fetchColumn() === 1, $permissionKey . ' is missing.');
    }

    $first = requireMpiSuccess(
        $patientService->createPatient(
            mpiPatient($suffix, '0817' . substr($suffix, -7)),
            $actorId
        ),
        'First MPI patient creation'
    );
    $firstPatientId = (int)$first['patient_id'];
    $patientIds[] = $firstPatientId;

    $secondData = mpiPatient($suffix, '0817' . substr($suffix, -7), true);
    $second = requireMpiSuccess(
        $patientService->createPatient($secondData, $actorId),
        'Reviewed duplicate patient creation'
    );
    $secondPatientId = (int)$second['patient_id'];
    $patientIds[] = $secondPatientId;

    $hospitalSearch = $patientService->searchPatientsPaginated([
        'query' => (string)$first['hospital_number']
    ], 1, 10);
    assertMpi(
        (int)($hospitalSearch['records'][0]['id'] ?? 0) === $firstPatientId
            && (int)($hospitalSearch['records'][0]['match_rank'] ?? 0) === 1,
        'Exact hospital-number priority failed.'
    );

    $nameSearch = $patientService->searchPatientsPaginated([
        'query' => 'Mpi' . $suffix
    ], 1, 1);
    assertMpi((int)$nameSearch['total_results'] === 2, 'Prefix-name search failed.');
    assertMpi(
        (int)$nameSearch['page_size'] === 1 && (int)$nameSearch['total_pages'] === 2,
        'MPI pagination metadata is incorrect.'
    );

    $phoneSearch = $patientService->searchPatientsPaginated([
        'query' => '0817' . substr($suffix, -7)
    ], 1, 10);
    assertMpi((int)$phoneSearch['total_results'] === 2, 'Exact phone search failed.');

    $identifierValue = 'NIN' . $suffix;
    $createdIdentifier = requireMpiSuccess($identifierService->addIdentifier([
        'patient_id' => $firstPatientId,
        'identifier_type' => 'National Identification Number',
        'identifier_value' => $identifierValue,
        'is_primary' => 1,
        'reason' => 'Milestone 2.2 focused verification.'
    ], $actorId), 'Identifier creation');
    $identifierId = (int)$createdIdentifier['identifier_id'];
    $identifierIds[] = $identifierId;

    assertMpi(
        count($identifierService->listIdentifiers($firstPatientId)) === 1,
        'Identifier list compatibility API failed.'
    );
    assertMpi(
        count($identifierService->searchIdentifier($identifierValue)) === 1,
        'Identifier search compatibility API failed.'
    );
    $found = $identifierService->findPatientByIdentifier(
        'National Identification Number',
        $identifierValue
    );
    assertMpi((int)($found['id'] ?? 0) === $firstPatientId, 'Exact identifier lookup failed.');

    $identifierSearch = $patientService->searchPatientsPaginated([
        'query' => $identifierValue
    ], 1, 10);
    assertMpi(
        (int)($identifierSearch['records'][0]['id'] ?? 0) === $firstPatientId
            && (int)($identifierSearch['records'][0]['match_rank'] ?? 0) === 2,
        'Exact alternate-identifier priority failed.'
    );
    $identifierPlan = $pdo->prepare('
        EXPLAIN SELECT patient_id FROM patient_identifiers
        WHERE identifier_type = :identifier_type
          AND normalized_value = :normalized_value
          AND is_active = 1
    ');
    $identifierPlan->execute([
        ':identifier_type' => 'National Identification Number',
        ':normalized_value' => $identifierService->normalizeIdentifier(
            'National Identification Number',
            $identifierValue
        )
    ]);
    $plan = $identifierPlan->fetch(PDO::FETCH_ASSOC);
    assertMpi(
        !empty($plan['key']) && (string)$plan['type'] !== 'ALL',
        'Exact identifier lookup is not using an index.'
    );

    $duplicateIdentifier = $identifierService->addIdentifier([
        'patient_id' => $secondPatientId,
        'identifier_type' => 'National Identification Number',
        'identifier_value' => $identifierValue,
        'reason' => 'Uniqueness verification.'
    ], $actorId);
    assertMpi(($duplicateIdentifier['success'] ?? true) === false, 'Unique identifier duplication was accepted.');

    $current = $identifierService->getIdentifierById($identifierId);
    $updated = requireMpiSuccess($identifierService->updateIdentifier(
        $identifierId,
        [
            'identifier_value' => $identifierValue . 'U',
            'reason' => 'Verify versioned identifier update.'
        ],
        (int)$current['version'],
        $actorId
    ), 'Identifier update');
    assertMpi((int)$updated['version'] === 2, 'Identifier version did not advance.');

    requireMpiSuccess($identifierService->verifyIdentifier(
        $identifierId,
        'Evidence verified in dedicated test database.',
        $actorId
    ), 'Identifier verification');
    requireMpiSuccess($identifierService->deactivateIdentifier(
        $identifierId,
        'Complete identifier lifecycle verification.',
        $actorId
    ), 'Identifier deactivation');
    assertMpi(
        count($identifierService->getIdentifierHistory($identifierId)) === 4,
        'Append-only identifier history is incomplete.'
    );

    $candidate = $pdo->prepare('
        SELECT * FROM patient_duplicate_candidates
        WHERE patient_id_low = :low_id AND patient_id_high = :high_id
    ');
    $candidate->execute([
        ':low_id' => min($firstPatientId, $secondPatientId),
        ':high_id' => max($firstPatientId, $secondPatientId)
    ]);
    $candidateRow = $candidate->fetch(PDO::FETCH_ASSOC);
    assertMpi((bool)$candidateRow, 'Duplicate candidate was not created.');
    assertMpi((float)$candidateRow['match_score'] >= 80, 'Duplicate score was unexpectedly low.');

    requireMpiSuccess($patientService->reviewDuplicateCandidate(
        (int)$candidateRow['id'],
        'Not Duplicate',
        'Controlled false-positive dismissal for test verification.',
        $actorId,
        (int)$candidateRow['version']
    ), 'Duplicate dismissal');
    $dismissedAudit = $pdo->prepare('
        SELECT COUNT(*) FROM audit_logs
        WHERE action = \'DUPLICATE_DISMISSED\'
          AND patient_id IN (:first_patient, :second_patient)
    ');
    $dismissedAudit->execute([
        ':first_patient' => $firstPatientId,
        ':second_patient' => $secondPatientId
    ]);
    assertMpi((int)$dismissedAudit->fetchColumn() === 2, 'Duplicate dismissal audit is incomplete.');

    assertMpi(
        $permissionService->canManagePatientIdentifiers($firstPatientId, null, $admin),
        'Administrator identifier permission override failed.'
    );
    $unauthorized = $admin;
    $unauthorized['role_name'] = 'Store Officer';
    $unauthorized['department_name'] = 'Store';
    assertMpi(
        !$permissionService->canReviewDuplicateCandidates($unauthorized),
        'Unauthorized duplicate-review permission was granted.'
    );

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    assertMpi(verifyCsrfToken($_SESSION['csrf_token']), 'Valid CSRF token was rejected.');
    assertMpi(!verifyCsrfToken('invalid-token'), 'Invalid CSRF token was accepted.');

    $auditCount = $pdo->prepare('
        SELECT COUNT(*) FROM audit_logs
        WHERE patient_id = :patient_id
          AND action IN (
              \'IDENTIFIER_CREATED\', \'IDENTIFIER_UPDATED\',
              \'IDENTIFIER_VERIFIED\', \'IDENTIFIER_DEACTIVATED\'
          )
    ');
    $auditCount->execute([':patient_id' => $firstPatientId]);
    assertMpi((int)$auditCount->fetchColumn() === 4, 'Identifier audit generation is incomplete.');

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
    $rollbackService = new PatientIdentifierService($pdo, $failingAudit);
    $rollbackValue = 'ROLLBACK' . $suffix;
    $rolledBack = $rollbackService->addIdentifier([
        'patient_id' => $firstPatientId,
        'identifier_type' => 'Passport Number',
        'identifier_value' => $rollbackValue,
        'reason' => 'Audit rollback verification.'
    ], $actorId);
    assertMpi(($rolledBack['success'] ?? true) === false, 'Audit failure did not fail the write.');
    $rollbackCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM patient_identifiers WHERE normalized_value = :value'
    );
    $rollbackCheck->execute([
        ':value' => $identifierService->normalizeIdentifier('Passport Number', $rollbackValue)
    ]);
    assertMpi((int)$rollbackCheck->fetchColumn() === 0, 'Failed audit did not roll back identifier data.');

    $downPath = __DIR__ . '/../database/migrations/016_phase2_patient_identifiers_mpi_down.sql';
    $upPath = __DIR__ . '/../database/migrations/016_phase2_patient_identifiers_mpi_up.sql';
    $downSql = (string)file_get_contents($downPath);
    $upSql = (string)file_get_contents($upPath);
    DatabaseSafety::assertSafeSchema($downSql, $resolved['live']);
    DatabaseSafety::assertSafeSchema($upSql, $resolved['live']);
    $pdo->exec($downSql);
    $settingCount = (int)$pdo->query(
        "SELECT COUNT(*) FROM system_settings WHERE setting_key = 'mpi.duplicate_threshold'"
    )->fetchColumn();
    assertMpi($settingCount === 0, 'Migration 016 down verification failed.');
    assertMpi(
        (bool)$pdo->query("SHOW TABLES LIKE 'patient_identifiers'")->fetchColumn(),
        'Compatibility rollback removed retained medical history.'
    );
    $pdo->exec($upSql);
    $settingCount = (int)$pdo->query(
        "SELECT COUNT(*) FROM system_settings WHERE setting_key = 'mpi.duplicate_threshold'"
    )->fetchColumn();
    assertMpi($settingCount === 1, 'Migration 016 up verification failed.');

    echo "Phase 2 Milestone 2.2 MPI tests passed on {$databaseName}." . PHP_EOL;
} finally {
    if ($patientIds !== []) {
        $placeholders = implode(',', array_fill(0, count($patientIds), '?'));
        $pdo->prepare(
            'DELETE FROM patient_duplicate_candidates
             WHERE patient_id_low IN (' . $placeholders . ')
                OR patient_id_high IN (' . $placeholders . ')'
        )->execute(array_merge($patientIds, $patientIds));
        $pdo->prepare(
            'DELETE FROM patient_identifier_history WHERE patient_id IN (' . $placeholders . ')'
        )->execute($patientIds);
        $pdo->prepare(
            'DELETE FROM patient_identifiers WHERE patient_id IN (' . $placeholders . ')'
        )->execute($patientIds);
        $pdo->prepare(
            'DELETE FROM audit_logs WHERE patient_id IN (' . $placeholders . ')'
        )->execute($patientIds);
        $pdo->prepare(
            'DELETE FROM patient_demographic_history WHERE patient_id IN (' . $placeholders . ')'
        )->execute($patientIds);
        $pdo->prepare(
            'DELETE FROM record_access_logs WHERE patient_id IN (' . $placeholders . ')'
        )->execute($patientIds);
        $pdo->prepare('DELETE FROM patients WHERE id IN (' . $placeholders . ')')
            ->execute($patientIds);
    }
}
