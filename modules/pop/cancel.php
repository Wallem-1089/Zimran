<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    http_response_code(400);
    exit('Invalid Plaster request.');
}

$request = $popService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Plaster request not found.');
}
$visit = popRequireVisit($visitService, (int)$request['visit_id']);
if (!$permissionService->canProcessPopRequest($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot cancel this Plaster request.');
}

popFlash($popService->cancelRequest($requestId, $currentUser), 'Plaster request cancelled.');
header('Location: view.php?id=' . $requestId);
exit;
