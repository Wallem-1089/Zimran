<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    http_response_code(400);
    exit('Invalid laboratory request.');
}

$request = $laboratoryService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Laboratory request not found.');
}

$visit = laboratoryRequireVisit($visitService, (int)$request['visit_id']);
if (!$permissionService->canProcessLaboratoryRequest($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot process this laboratory request.');
}

$result = $laboratoryService->startRequest($requestId, $currentUser);
laboratoryFlash($result, 'Laboratory request started.');

header('Location: view.php?id=' . $requestId);
exit;
