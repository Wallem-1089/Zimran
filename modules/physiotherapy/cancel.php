<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    http_response_code(400);
    exit('Invalid physiotherapy request.');
}

$request = $physiotherapyService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Physiotherapy request not found.');
}

$visit = physiotherapyRequireVisit($visitService, (int)$request['visit_id']);
if (!$permissionService->canProcessPhysiotherapyRequest($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot cancel this physiotherapy request.');
}

$result = $physiotherapyService->cancelRequest($requestId, $currentUser);
physiotherapyFlash($result, 'Physiotherapy request cancelled.');

header('Location: view.php?id=' . $requestId);
exit;


