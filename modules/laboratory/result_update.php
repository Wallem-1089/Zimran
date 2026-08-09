<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'laboratory_request_id', FILTER_VALIDATE_INT) ?: 0;
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
if (!$permissionService->canEditLaboratoryResult($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot edit this laboratory result.');
}

$result = $laboratoryService->updateResult($_POST, $currentUser);
laboratoryFlash($result, 'Laboratory result updated.');

header('Location: result.php?id=' . $requestId);
exit;
