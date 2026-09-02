<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$apply = in_array('--apply', $argv, true);
$keepVisitNumber = 'VIS-2026-000024';

function ids(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function scalar(PDO $pdo, string $sql, array $params = []): mixed
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function placeholders(array $values): string
{
    return implode(',', array_fill(0, count($values), '?'));
}

function deleteWhereIds(PDO $pdo, string $table, string $column, array $values): int
{
    if ($values === []) {
        return 0;
    }
    if (!tableExists($pdo, $table)) {
        return 0;
    }
    if (!columnExists($pdo, $table, $column)) {
        return 0;
    }
    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$column} IN (" . placeholders($values) . ')');
    $stmt->execute($values);
    return $stmt->rowCount();
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = ?
    ');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = ?
          AND column_name = ?
    ');
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}

$keepVisitId = (int)scalar($pdo, 'SELECT id FROM visits WHERE visit_number = ?', [$keepVisitNumber]);
if ($keepVisitId <= 0) {
    throw new RuntimeException('Keep visit was not found: ' . $keepVisitNumber);
}

$keepPatientId = (int)scalar($pdo, 'SELECT patient_id FROM visits WHERE id = ?', [$keepVisitId]);

$targetVisitIds = ids(
    $pdo,
    "SELECT DISTINCT v.id
     FROM visits v
     INNER JOIN patients p ON p.id = v.patient_id
     WHERE p.first_name = 'E2E'
       AND p.last_name LIKE 'HospitalFlow%'
       AND v.id <> ?",
    [$keepVisitId]
);

$targetPatientIds = ids(
    $pdo,
    "SELECT p.id
     FROM patients p
     WHERE p.first_name = 'E2E'
       AND p.last_name LIKE 'HospitalFlow%'
       AND p.id <> ?",
    [$keepPatientId]
);

$targetDocumentIds = $targetVisitIds === []
    ? []
    : ids($pdo, 'SELECT id FROM medical_documents WHERE visit_id IN (' . placeholders($targetVisitIds) . ')', $targetVisitIds);

$targetClinicalNoteIds = $targetVisitIds === []
    ? []
    : ids($pdo, 'SELECT id FROM clinical_notes WHERE visit_id IN (' . placeholders($targetVisitIds) . ')', $targetVisitIds);

$targetAllergyIds = $targetVisitIds === []
    ? []
    : ids($pdo, 'SELECT id FROM patient_allergies WHERE source_visit_id IN (' . placeholders($targetVisitIds) . ')', $targetVisitIds);

$targetAlertIds = $targetVisitIds === []
    ? []
    : ids($pdo, 'SELECT id FROM patient_alerts WHERE visit_id IN (' . placeholders($targetVisitIds) . ')', $targetVisitIds);

$targetProblemIds = $targetVisitIds === []
    ? []
    : ids($pdo, 'SELECT id FROM patient_problems WHERE source_visit_id IN (' . placeholders($targetVisitIds) . ')', $targetVisitIds);

$targetMedicalHistoryIds = $targetVisitIds === []
    ? []
    : ids($pdo, 'SELECT id FROM patient_medical_history WHERE source_visit_id IN (' . placeholders($targetVisitIds) . ')', $targetVisitIds);

$targetInventoryIds = ids(
    $pdo,
    "SELECT id FROM inventory_items
     WHERE item_code LIKE 'E2E-%'
       AND item_code NOT LIKE 'E2E-%20260902140328'"
);

$targetBillableIds = ids(
    $pdo,
    "SELECT id FROM billable_items
     WHERE item_code LIKE 'E2E-%'
       AND item_code NOT LIKE 'E2E-%20260902140328'"
);

$targetStockRequestIds = $targetInventoryIds === []
    ? []
    : ids(
        $pdo,
        'SELECT DISTINCT stock_request_id
         FROM stock_request_items
         WHERE inventory_item_id IN (' . placeholders($targetInventoryIds) . ')',
        $targetInventoryIds
    );

$targetWardIds = ids(
    $pdo,
    "SELECT id FROM wards
     WHERE ward_name LIKE 'E2E Observation Ward%'
       AND ward_code <> 'E2E140328'"
);

$counts = [
    'keep_visit_id' => $keepVisitId,
    'keep_patient_id' => $keepPatientId,
    'target_patients' => count($targetPatientIds),
    'target_visits' => count($targetVisitIds),
    'target_documents' => count($targetDocumentIds),
    'target_clinical_notes' => count($targetClinicalNoteIds),
    'target_allergies' => count($targetAllergyIds),
    'target_alerts' => count($targetAlertIds),
    'target_problems' => count($targetProblemIds),
    'target_medical_history' => count($targetMedicalHistoryIds),
    'target_stock_requests' => count($targetStockRequestIds),
    'target_inventory_items' => count($targetInventoryIds),
    'target_billable_items' => count($targetBillableIds),
    'target_e2e_wards' => count($targetWardIds),
];

echo json_encode($counts, JSON_PRETTY_PRINT) . PHP_EOL;

if (!$apply) {
    echo "DRY RUN ONLY. Re-run with --apply to delete failed E2E smoke records." . PHP_EOL;
    exit(0);
}

$pdo->beginTransaction();
try {
    $deleted = [];

    if ($targetDocumentIds !== []) {
        $deleted['medical_document_versions'] = deleteWhereIds($pdo, 'medical_document_versions', 'document_id', $targetDocumentIds);
    }
    if ($targetClinicalNoteIds !== []) {
        $deleted['clinical_note_versions'] = deleteWhereIds($pdo, 'clinical_note_versions', 'note_id', $targetClinicalNoteIds);
        $deleted['clinical_note_amendments'] = deleteWhereIds($pdo, 'clinical_note_amendments', 'note_id', $targetClinicalNoteIds);
    }
    if ($targetAllergyIds !== []) {
        $deleted['patient_allergy_history'] = deleteWhereIds($pdo, 'patient_allergy_history', 'allergy_id', $targetAllergyIds);
    }
    if ($targetAlertIds !== []) {
        $deleted['patient_alert_history'] = deleteWhereIds($pdo, 'patient_alert_history', 'alert_id', $targetAlertIds);
    }
    if ($targetProblemIds !== []) {
        $deleted['patient_problem_history'] = deleteWhereIds($pdo, 'patient_problem_history', 'problem_id', $targetProblemIds);
    }
    if ($targetMedicalHistoryIds !== []) {
        $deleted['patient_medical_history_versions'] = deleteWhereIds($pdo, 'patient_medical_history_versions', 'history_entry_id', $targetMedicalHistoryIds);
    }

    foreach ([
        'pharmacy_dispensing',
        'medication_administration_records',
        'prescriptions',
        'patient_stock_usage',
        'payments',
        'invoices',
        'patient_charges',
        'billing_requests',
        'clinical_notes',
        'medical_documents',
        'admission_movements',
        'admissions',
        'physiotherapy_sessions',
        'physiotherapy_records',
        'theatre_records',
        'pop_records',
        'pop_requests',
        'ecg_reports',
        'ecg_requests',
        'radiology_reports',
        'radiology_requests',
        'laboratory_results',
        'laboratory_requests',
        'diabetes_monitoring',
        'dressing_records',
        'nursing_assessments',
        'vital_signs',
        'consultations',
        'department_notifications',
        'user_notifications',
        'encounter_events',
        'audit_logs',
    ] as $table) {
        if ($targetVisitIds !== []) {
            $deleted[$table] = deleteWhereIds($pdo, $table, 'visit_id', $targetVisitIds);
        }
    }

    $deleted['patient_allergies'] = deleteWhereIds($pdo, 'patient_allergies', 'id', $targetAllergyIds);
    $deleted['patient_alerts'] = deleteWhereIds($pdo, 'patient_alerts', 'id', $targetAlertIds);
    $deleted['patient_problems'] = deleteWhereIds($pdo, 'patient_problems', 'id', $targetProblemIds);
    $deleted['patient_medical_history'] = deleteWhereIds($pdo, 'patient_medical_history', 'id', $targetMedicalHistoryIds);

    if ($targetStockRequestIds !== []) {
        $deleted['stock_request_items'] = deleteWhereIds($pdo, 'stock_request_items', 'stock_request_id', $targetStockRequestIds);
        $deleted['stock_requests'] = deleteWhereIds($pdo, 'stock_requests', 'id', $targetStockRequestIds);
    }

    if ($targetInventoryIds !== []) {
        $deleted['stock_transactions'] = deleteWhereIds($pdo, 'stock_transactions', 'inventory_item_id', $targetInventoryIds);
        $deleted['department_stock_balances'] = deleteWhereIds($pdo, 'department_stock_balances', 'inventory_item_id', $targetInventoryIds);
        $deleted['inventory_items'] = deleteWhereIds($pdo, 'inventory_items', 'id', $targetInventoryIds);
    }

    if ($targetBillableIds !== []) {
        $deleted['billable_items'] = deleteWhereIds($pdo, 'billable_items', 'id', $targetBillableIds);
    }

    if ($targetWardIds !== []) {
        $deleted['ward_beds'] = deleteWhereIds($pdo, 'ward_beds', 'ward_id', $targetWardIds);
        $deleted['wards'] = deleteWhereIds($pdo, 'wards', 'id', $targetWardIds);
    }

    if ($targetVisitIds !== []) {
        $deleted['visits'] = deleteWhereIds($pdo, 'visits', 'id', $targetVisitIds);
    }
    if ($targetPatientIds !== []) {
        $deleted['patient_demographic_history'] = deleteWhereIds($pdo, 'patient_demographic_history', 'patient_id', $targetPatientIds);
        $deleted['patient_identifier_history'] = deleteWhereIds($pdo, 'patient_identifier_history', 'patient_id', $targetPatientIds);
        $deleted['patient_identifiers'] = deleteWhereIds($pdo, 'patient_identifiers', 'patient_id', $targetPatientIds);
        $deleted['patient_duplicate_candidates'] = deleteWhereIds($pdo, 'patient_duplicate_candidates', 'patient_id', $targetPatientIds);
        $deleted['patient_duplicate_candidates_duplicate'] = deleteWhereIds($pdo, 'patient_duplicate_candidates', 'duplicate_patient_id', $targetPatientIds);
        $deleted['audit_logs_by_patient'] = deleteWhereIds($pdo, 'audit_logs', 'patient_id', $targetPatientIds);
        $deleted['patients'] = deleteWhereIds($pdo, 'patients', 'id', $targetPatientIds);
    }

    $pdo->commit();
    echo json_encode(['deleted' => $deleted], JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
