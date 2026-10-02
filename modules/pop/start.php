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
if (!$permissionService->canRecordPopProcedure($visit, $currentUser) && !$permissionService->canEditPopRecord($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot record this Plaster procedure.');
}

$_SESSION['success_message'] = 'Use Record Plaster Procedure to work on this Plaster request.';
header('Location: record.php?id=' . $requestId);
exit;
