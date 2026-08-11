<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$prescriptionId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$prescriptionId) {
    http_response_code(400);
    exit('Invalid prescription.');
}

if (!$pharmacyTablesReady) {
    http_response_code(503);
    exit('Pharmacy tables are not available yet. Apply Migration 032 to enable this section.');
}

$existing = $pharmacyService->getPrescriptionById($prescriptionId, $currentUser);
if (!$existing) {
    http_response_code(404);
    exit('Prescription not found.');
}

$visit = pharmacyRequireVisit($visitService, (int)$existing['visit_id']);
if (!$permissionService->canEditPrescription($visit, $currentUser, (string)$existing['prescription_source'])) {
    http_response_code(403);
    exit('Prescription edit is denied.');
}

$result = $pharmacyService->updatePrescription($prescriptionId, $_POST, $currentUser);
if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to update prescription.'];
    $_SESSION['old_pharmacy_prescription'] = [
        'id' => $prescriptionId,
        'visit_id' => (int)$existing['visit_id'],
        'patient_id' => (int)$existing['patient_id'],
        'prescription_source' => (string)$existing['prescription_source'],
        'inventory_item_id' => (string)($_POST['inventory_item_id'] ?? ''),
        'medication_name' => (string)($_POST['medication_name'] ?? ''),
        'dosage' => (string)($_POST['dosage'] ?? ''),
        'frequency' => (string)($_POST['frequency'] ?? ''),
        'duration' => (string)($_POST['duration'] ?? ''),
        'quantity' => (string)($_POST['quantity'] ?? ''),
        'instructions' => (string)($_POST['instructions'] ?? ''),
    ];

    header('Location: edit.php?id=' . $prescriptionId);
    exit;
}

$_SESSION['success_message'] = 'Prescription updated.';
header('Location: view.php?id=' . $prescriptionId);
exit;
