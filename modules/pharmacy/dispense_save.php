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
if (!$permissionService->canDispensePrescription($visit, $currentUser)) {
    http_response_code(403);
    exit('Prescription dispensing is denied.');
}

$result = $pharmacyService->dispense($prescriptionId, $_POST, $currentUser);
if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to dispense prescription.'];
    header('Location: dispense.php?id=' . $prescriptionId);
    exit;
}

$_SESSION['success_message'] = 'Prescription dispensed.';
header('Location: view.php?id=' . $prescriptionId);
exit;
