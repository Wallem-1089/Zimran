<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$assessmentId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$existing = $nursingService->getById($assessmentId, $currentUser);
if (!$existing) {
    http_response_code(404);
    exit('Nursing assessment not found.');
}

$visit = nursingRequireVisit($visitService, (int)$existing['visit_id']);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canCompleteNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('Nursing completion is denied.');
}

$result = $nursingService->complete($assessmentId, $currentUser);
if (($result['success'] ?? false) === true) {
    $_SESSION['success_message'] = 'Nursing assessment completed.';
    header('Location: view.php?id=' . $assessmentId);
    exit;
}

$_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to complete nursing assessment.'];
$_SESSION['error_message'] = 'Unable to complete nursing assessment.';
header('Location: view.php?id=' . $assessmentId);
exit;
