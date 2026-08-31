<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'pop_request_id', FILTER_VALIDATE_INT) ?: 0;
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
if (!$permissionService->canRecordPopProcedure($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot record this POP procedure.');
}

popFlash($popService->saveRecord($_POST, $currentUser), 'POP record saved.');
header('Location: record.php?id=' . $requestId);
exit;
