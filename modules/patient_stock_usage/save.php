<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../patients/search.php');
    exit;
}

requireCsrfToken();

if (!$patientStockUsageTablesReady) {
    http_response_code(503);
    exit('Patient Stock Usage tables are not available yet. Apply Migration 053 to enable this section.');
}

$visitId = (int)($_POST['visit_id'] ?? 0);
$result = $patientStockUsageService->createUsage($_POST, $currentUser);

if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to record patient stock usage.'];
    $_SESSION['old_patient_stock_usage'] = $_POST;
    header('Location: create.php?visit=' . $visitId);
    exit;
}

$_SESSION['success_message'] = 'Patient stock usage recorded.';
header('Location: view.php?id=' . (int)$result['patient_stock_usage_id']);
exit;
