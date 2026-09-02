<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'radiology_request_id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    http_response_code(400);
    exit('Invalid radiology request.');
}

$request = $radiologyService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Radiology request not found.');
}

$visit = radiologyRequireVisit($visitService, (int)$request['visit_id']);
if (!$permissionService->canEnterRadiologyResult($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot enter this radiology result.');
}

$result = $radiologyService->saveResult($_POST, $currentUser, $_FILES['radiology_chart'] ?? null);
radiologyFlash($result, 'Radiology report saved.');

header('Location: report.php?id=' . $requestId);
exit;

