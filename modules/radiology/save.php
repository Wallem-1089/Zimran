<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$visitId = filter_input(INPUT_POST, 'visit_id', FILTER_VALIDATE_INT) ?: 0;
$requestSource = radiologyRequestSourceLabel((string)($_POST['request_source'] ?? 'Clinical'));

if (!$visitId) {
    http_response_code(400);
    exit('Invalid encounter.');
}

$visit = radiologyRequireVisit($visitService, $visitId);
radiologyRequireAccess($permissionService, $visit, $currentUser, $requestSource);

if (!$radiologyTablesReady) {
    http_response_code(503);
    exit('Radiology tables are not available yet. Apply Migration 027 to enable this section.');
}

$result = $radiologyService->createRequest($_POST, $currentUser);

if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to save radiology request.'];
    $_SESSION['old_radiology_request'] = [
        'request_source' => $requestSource,
        'priority' => (string)($_POST['priority'] ?? 'Routine'),
        'study_requested' => (string)($_POST['study_requested'] ?? $_POST['tests_requested'] ?? ''),
        'clinical_indication' => (string)($_POST['clinical_indication'] ?? $_POST['clinical_information'] ?? ''),
    ];

    header('Location: request.php?visit=' . $visitId . '&source=' . urlencode($requestSource));
    exit;
}

$_SESSION['success_message'] = 'Radiology request saved.';
header('Location: view.php?id=' . (int)$result['radiology_request_id']);
exit;

