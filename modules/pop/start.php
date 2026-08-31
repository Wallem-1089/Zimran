<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    http_response_code(400);
    exit('Invalid POP request.');
}

$request = $popService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('POP request not found.');
}
$visit = popRequireVisit($visitService, (int)$request['visit_id']);
if (!$permissionService->canProcessPopRequest($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot start this POP request.');
}

popFlash($popService->startRequest($requestId, $currentUser), 'POP request started.');
header('Location: view.php?id=' . $requestId);
exit;
