<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    http_response_code(400);
    exit('Invalid ECG request.');
}

$request = $ecgService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('ECG request not found.');
}

$visit = ecgRequireVisit($visitService, (int)$request['visit_id']);
if (!$permissionService->canProcessEcgRequest($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot cancel this ECG request.');
}

$result = $ecgService->cancelRequest($requestId, $currentUser);
ecgFlash($result, 'ECG request cancelled.');

header('Location: view.php?id=' . $requestId);
exit;

