<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$visitId = filter_input(INPUT_POST, 'visit_id', FILTER_VALIDATE_INT) ?: 0;
$requestSource = pharmacyRequestSourceLabel((string)($_POST['prescription_source'] ?? 'Clinical'));

if (!$visitId) {
    http_response_code(400);
    exit('Invalid encounter.');
}

$visit = pharmacyRequireVisit($visitService, $visitId);
if (!$pharmacyTablesReady) {
    http_response_code(503);
    exit('Pharmacy tables are not available yet. Apply Migration 032 to enable this section.');
}

if (!$permissionService->canCreatePrescription($visit, $currentUser, $requestSource)) {
    http_response_code(403);
    exit('You do not have permission to create this prescription.');
}

$result = $pharmacyService->createPrescription($_POST, $currentUser);
if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to save prescription.'];
    $_SESSION['old_pharmacy_prescription'] = [
        'visit_id' => $visitId,
        'patient_id' => (int)$visit['patient_id'],
        'prescription_source' => $requestSource,
        'inventory_item_id' => (string)($_POST['inventory_item_id'] ?? ''),
        'medication_name' => (string)($_POST['medication_name'] ?? ''),
        'dosage' => (string)($_POST['dosage'] ?? ''),
        'frequency' => (string)($_POST['frequency'] ?? ''),
        'duration' => (string)($_POST['duration'] ?? ''),
        'quantity' => (string)($_POST['quantity'] ?? ''),
        'instructions' => (string)($_POST['instructions'] ?? ''),
    ];

    header('Location: prescribe.php?visit=' . $visitId . '&source=' . urlencode($requestSource));
    exit;
}

$_SESSION['success_message'] = 'Prescription saved.';
header('Location: view.php?id=' . (int)$result['prescription_id']);
exit;
