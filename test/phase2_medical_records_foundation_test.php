<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/MedicalRecordService.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/PermissionService.php';

function assertMedicalRecords(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function successfulMedicalRecords(array $result, string $operation): array
{
    assertMedicalRecords(
        ($result['success'] ?? false) === true,
        $operation . ' failed: ' . implode(' ', $result['errors'] ?? [])
    );

    return $result;
}

$admin = $pdo->query('
    SELECT u.*, d.department_name, r.role_name
    FROM users u
    INNER JOIN departments d ON d.id = u.department_id
    INNER JOIN roles r ON r.id = u.role_id
    WHERE u.username = \'walter\'
    ORDER BY u.id
    LIMIT 1
')->fetch(PDO::FETCH_ASSOC);

assertMedicalRecords((bool)$admin, 'A super administrator account is required.');

$_SESSION['user'] = $admin;
$_SESSION['active_department_id'] = (int)$admin['department_id'];
$_SESSION['user']['active_department_id'] = (int)$admin['department_id'];

$patientId = null;

try {
    foreach ([
        'record_amendments',
        'patient_demographic_history',
        'record_access_logs'
    ] as $table) {
        $exists = $pdo->prepare('
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
        ');
        $exists->execute([':table_name' => $table]);
        assertMedicalRecords((int)$exists->fetchColumn() === 1, $table . ' is missing.');
    }

    $permissionService = new PermissionService($pdo);
    $patientService = new PatientService($pdo);
    $medicalRecordService = new MedicalRecordService($pdo);
    $unique = date('YmdHis') . random_int(1000, 9999);

    foreach ([
        'view_medical_record',
        'edit_patient_demographics',
        'view_patient_audit_history'
    ] as $permissionKey) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key'
        );
        $stmt->execute([':permission_key' => $permissionKey]);
        assertMedicalRecords((int)$stmt->fetchColumn() === 1, $permissionKey . ' is missing.');
    }

    $patient = successfulMedicalRecords($patientService->createPatient([
        'first_name' => 'PhaseTwo',
        'middle_name' => 'Foundation',
        'last_name' => 'Chart' . $unique,
        'gender' => 'Unknown',
        'date_of_birth' => '1985-05-15',
        'marital_status' => 'Single',
        'occupation' => 'Verification',
        'phone' => 'P2-' . substr($unique, -8),
        'email' => '',
        'address' => 'Initial address',
        'state_of_origin' => 'Edo',
        'nationality' => 'Nigerian',
        'blood_group' => 'O+',
        'genotype' => 'AA',
        'allergies' => '',
        'next_of_kin' => 'Foundation Kin',
        'next_of_kin_relationship' => 'Sibling',
        'next_of_kin_phone' => '08000000000'
    ], (int)$admin['id']), 'Patient creation');
    $patientId = (int)$patient['patient_id'];

    assertMedicalRecords(
        method_exists($patientService, 'createPatient')
            && method_exists($patientService, 'updatePatient')
            && method_exists($patientService, 'getPatientById')
            && method_exists($patientService, 'searchPatients'),
        'A stable PatientService API is missing.'
    );

    assertMedicalRecords(
        $permissionService->canViewMedicalRecord($patientId, $admin),
        'Super Administrator chart override failed.'
    );

    $storeRoleId = (int)$pdo->query(
        "SELECT id FROM roles WHERE role_name = 'Store Officer' LIMIT 1"
    )->fetchColumn();
    $unauthorized = $admin;
    $unauthorized['role_id'] = $storeRoleId;
    $unauthorized['role_name'] = 'Store Officer';
    $unauthorized['department_name'] = 'Store';
    $unauthorized['department_id'] = 12;
    $unauthorized['active_department_id'] = 12;
    assertMedicalRecords(
        !$permissionService->canViewMedicalRecord($patientId, $unauthorized),
        'An unauthorized role received chart access.'
    );

    $accessBefore = (int)$pdo->query(
        'SELECT COUNT(*) FROM record_access_logs WHERE patient_id = ' . $patientId
    )->fetchColumn();
    $chart = successfulMedicalRecords(
        $medicalRecordService->getPatientChart($patientId, $admin),
        'Patient Chart load'
    );
    assertMedicalRecords(
        (int)$chart['data']['patient']['id'] === $patientId,
        'Patient Chart returned the wrong patient.'
    );
    $accessAfter = (int)$pdo->query(
        'SELECT COUNT(*) FROM record_access_logs WHERE patient_id = ' . $patientId
    )->fetchColumn();
    assertMedicalRecords($accessAfter === $accessBefore + 1, 'Chart access was not logged once.');

    $current = $patientService->getPatientById($patientId);
    assertMedicalRecords((int)$current['demographic_version'] === 1, 'Initial version is incorrect.');
    $updatedData = $current;
    $updatedData['address'] = 'Versioned address';
    $updatedData['occupation'] = 'Medical Records Verification';

    $updated = successfulMedicalRecords($patientService->updatePatientWithContext(
        $patientId,
        $updatedData,
        'Correct address and occupation for Milestone 2.1 verification.',
        1,
        (int)$admin['id']
    ), 'Versioned demographic update');
    assertMedicalRecords(
        (int)$updated['demographic_version'] === 2,
        'The demographic version did not advance.'
    );

    $history = successfulMedicalRecords(
        $medicalRecordService->getDemographicHistory($patientId),
        'Demographic history retrieval'
    );
    assertMedicalRecords(count($history['data']) === 1, 'Exactly one history entry was expected.');
    assertMedicalRecords(
        in_array('address', $history['data'][0]['changed_fields'], true),
        'Changed fields do not include address.'
    );

    $staleData = $updatedData;
    $staleData['address'] = 'Must not overwrite';
    $stale = $patientService->updatePatientWithContext(
        $patientId,
        $staleData,
        'Attempt a stale update.',
        1,
        (int)$admin['id']
    );
    assertMedicalRecords(
        !$stale['success'] && ($stale['conflict'] ?? false),
        'A stale demographic update was not rejected.'
    );
    $afterStale = $patientService->getPatientById($patientId);
    assertMedicalRecords(
        $afterStale['address'] === 'Versioned address',
        'A stale update overwrote current demographic data.'
    );

    $actions = $pdo->prepare('
        SELECT action, COUNT(*) AS total
        FROM audit_logs
        WHERE patient_id = :patient_id
          AND action IN (
              \'MEDICAL_RECORD_VIEWED\',
              \'DEMOGRAPHICS_UPDATED\',
              \'DEMOGRAPHIC_HISTORY_CREATED\',
              \'DEMOGRAPHIC_UPDATE_REJECTED\'
          )
        GROUP BY action
    ');
    $actions->execute([':patient_id' => $patientId]);
    $actions = array_column($actions->fetchAll(PDO::FETCH_ASSOC), 'total', 'action');
    foreach ([
        'MEDICAL_RECORD_VIEWED',
        'DEMOGRAPHICS_UPDATED',
        'DEMOGRAPHIC_HISTORY_CREATED',
        'DEMOGRAPHIC_UPDATE_REJECTED'
    ] as $action) {
        assertMedicalRecords(!empty($actions[$action]), 'Missing audit event: ' . $action);
    }

    echo 'PASS: Phase 2.1 Patient Chart, demographic history, concurrency, PHI access, permissions, and audit foundation.' . PHP_EOL;
} finally {
    if ($patientId !== null) {
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM record_access_logs WHERE patient_id = :patient_id')
            ->execute([':patient_id' => $patientId]);
        $pdo->prepare('DELETE FROM patient_demographic_history WHERE patient_id = :patient_id')
            ->execute([':patient_id' => $patientId]);
        $pdo->prepare('DELETE FROM record_amendments WHERE patient_id = :patient_id')
            ->execute([':patient_id' => $patientId]);
        $pdo->prepare('DELETE FROM audit_logs WHERE patient_id = :patient_id')
            ->execute([':patient_id' => $patientId]);
        $pdo->prepare('DELETE FROM patients WHERE id = :patient_id')
            ->execute([':patient_id' => $patientId]);
        $pdo->commit();
    }
}
