<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/PatientService.php';
require_once __DIR__ . '/../services/PatientIdentifierService.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$userId = (int)$pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
$patient = $pdo->query('SELECT * FROM patients ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$createdPatientId = null;

if (!$patient && $userId > 0) {
    $stmt = $pdo->prepare('
        INSERT INTO patients (
            hospital_number, first_name, normalized_first_name,
            last_name, normalized_last_name, gender, date_of_birth,
            phone, normalized_phone, registered_by
        ) VALUES (
            :hospital_number, \'Milestone\', \'milestone\',
            \'Fixture\', \'fixture\', \'Unknown\', \'1990-01-01\',
            :phone, :normalized_phone, :registered_by
        )
    ');
    $suffix = (string)random_int(100000, 999999);
    $stmt->execute([
        ':hospital_number' => 'TEST-M22-' . $suffix,
        ':phone' => '080' . $suffix,
        ':normalized_phone' => '080' . $suffix,
        ':registered_by' => $userId
    ]);
    $createdPatientId = (int)$pdo->lastInsertId();
    $patient = $pdo->query('SELECT * FROM patients WHERE id = ' . $createdPatientId)
        ->fetch(PDO::FETCH_ASSOC);
}

if (!$patient || $userId <= 0) {
    fwrite(STDERR, "A patient and user fixture are required.\n");
    exit(1);
}

$service = new PatientIdentifierService($pdo);
$patientService = new PatientService($pdo);
$value = 'M22' . date('YmdHis') . random_int(1000, 9999);
$identifierId = null;

try {
    $created = $service->addIdentifier([
        'patient_id' => (int)$patient['id'],
        'identifier_type' => 'National Identification Number',
        'identifier_value' => $value,
        'reason' => 'Milestone 2.2 isolated verification.'
    ], $userId);
    $assert($created['success'] === true, 'Valid identifier creation failed.');
    $identifierId = (int)($created['identifier_id'] ?? 0);

    $duplicate = $service->addIdentifier([
        'patient_id' => (int)$patient['id'],
        'identifier_type' => 'National Identification Number',
        'identifier_value' => $value,
        'reason' => 'Duplicate verification.'
    ], $userId);
    $assert($duplicate['success'] === false, 'Duplicate unique identifier was accepted.');

    $found = $service->findPatientByIdentifier(
        'National Identification Number',
        $value
    );
    $assert((int)($found['id'] ?? 0) === (int)$patient['id'], 'Exact identifier lookup failed.');

    $mpi = $patientService->searchPatientsPaginated([
        'alternate_identifier' => $value
    ], 1, 10);
    $assert(($mpi['total_results'] ?? 0) === 1, 'MPI exact identifier search failed.');

    $row = $service->getIdentifierById($identifierId);
    $stale = $service->updateIdentifier(
        $identifierId,
        ['reason' => 'Stale update.', 'identifier_value' => $value],
        999,
        $userId
    );
    $assert($stale['success'] === false && !empty($stale['conflict']), 'Stale update was not rejected.');

    $verified = $service->verifyIdentifier(
        $identifierId,
        'Test evidence verified.',
        $userId
    );
    $assert($verified['success'] === true, 'Identifier verification failed.');

    $history = $service->getIdentifierHistory($identifierId);
    $assert(count($history) === 2, 'Identifier history is incomplete.');
    $assert(!str_contains($service->maskIdentifier(
        'National Identification Number',
        $value
    ), substr($value, 0, -4)), 'Sensitive identifier masking failed.');

    $possible = $patientService->findPossibleDuplicates($patient);
    $assert($possible['success'] === true, 'Deterministic duplicate evaluation failed.');
} finally {
    if ($identifierId) {
        $pdo->prepare('DELETE FROM patient_identifier_history WHERE identifier_id = ?')
            ->execute([$identifierId]);
        $pdo->prepare('DELETE FROM patient_identifiers WHERE id = ?')
            ->execute([$identifierId]);
        $pdo->prepare("DELETE FROM audit_logs WHERE patient_id = ? AND action LIKE 'IDENTIFIER_%'")
            ->execute([(int)$patient['id']]);
    }
    if ($createdPatientId) {
        $pdo->prepare('DELETE FROM patients WHERE id = ?')->execute([$createdPatientId]);
    }
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "Phase 2 Milestone 2.2 focused tests passed.\n";
