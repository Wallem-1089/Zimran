<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$visitId = filter_input(INPUT_POST, 'visit_id', FILTER_VALIDATE_INT) ?: 0;
$requestSource = laboratoryRequestSourceLabel((string)($_POST['request_source'] ?? 'Clinical'));

if (!$visitId) {
    http_response_code(400);
    exit('Invalid encounter.');
}

$visit = laboratoryRequireVisit($visitService, $visitId);
laboratoryRequireAccess($permissionService, $visit, $currentUser, $requestSource);

if (!$laboratoryTablesReady) {
    http_response_code(503);
    exit('Laboratory tables are not available yet. Apply Migration 025 to enable this section.');
}

$result = $laboratoryService->createRequest($_POST, $currentUser);

if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to save laboratory request.'];
    $_SESSION['old_laboratory_request'] = [
        'request_source' => $requestSource,
        'priority' => (string)($_POST['priority'] ?? 'Routine'),
        'tests_requested' => (string)($_POST['tests_requested'] ?? ''),
        'clinical_information' => (string)($_POST['clinical_information'] ?? ''),
    ];

    header('Location: create.php?visit=' . $visitId . '&source=' . urlencode($requestSource));
    exit;
}

$_SESSION['success_message'] = 'Laboratory request saved.';
header('Location: view.php?id=' . (int)$result['laboratory_request_id']);
exit;
