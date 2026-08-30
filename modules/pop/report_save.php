<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'POP_request_id', FILTER_VALIDATE_INT) ?: 0;
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
if (!$permissionService->canUploadPOPChart($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot upload this POP chart.');
}

$result = $POPService->saveReport($_POST, $currentUser, $_FILES['POP_chart'] ?? null);
POPFlash($result, 'POP chart and notes saved.');

header('Location: report.php?id=' . $requestId);
exit;

