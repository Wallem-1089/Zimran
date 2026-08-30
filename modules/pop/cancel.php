<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    http_response_code(400);
    exit('Invalid POP request.');
}

$request = $POPService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('POP request not found.');
}

$visit = POPRequireVisit($visitService, (int)$request['visit_id']);
if (!$permissionService->canProcessPOPRequest($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot cancel this POP request.');
}

$result = $POPService->cancelRequest($requestId, $currentUser);
POPFlash($result, 'POP request cancelled.');

header('Location: view.php?id=' . $requestId);
exit;

