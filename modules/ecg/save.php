<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$visitId = filter_input(INPUT_POST, 'visit_id', FILTER_VALIDATE_INT) ?: 0;
$requestSource = ecgRequestSourceLabel((string)($_POST['request_source'] ?? 'Clinical'));

if (!$visitId) {
    http_response_code(400);
    exit('Invalid encounter.');
}

$visit = ecgRequireVisit($visitService, $visitId);
ecgRequireCreateAccess($permissionService, $visit, $currentUser, $requestSource);

if (!$ecgTablesReady) {
    http_response_code(503);
    exit('ECG tables are not available yet. Apply Migration 058 to enable this section.');
}

$result = $ecgService->createRequest($_POST, $currentUser);

if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to save ECG request.'];
    $_SESSION['old_ecg_request'] = [
        'request_source' => $requestSource,
        'priority' => (string)($_POST['priority'] ?? 'Routine'),
        'study_requested' => (string)($_POST['study_requested'] ?? 'ECG'),
        'clinical_indication' => (string)($_POST['clinical_indication'] ?? ''),
    ];

    header('Location: request.php?visit=' . $visitId . '&source=' . urlencode($requestSource));
    exit;
}

$_SESSION['success_message'] = 'ECG request saved.';
header('Location: view.php?id=' . (int)$result['ecg_request_id']);
exit;

