<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$requestId = filter_input(INPUT_POST, 'ecg_request_id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    http_response_code(400);
    exit('Invalid ECG request.');
}

$request = $ecgService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('ECG request not found.');
}

$visit = ecgRequireVisit($visitService, (int)$request['visit_id']);
if (!$permissionService->canUploadEcgChart($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot upload this ECG chart.');
}

$result = $ecgService->saveReport($_POST, $currentUser, $_FILES['ecg_chart'] ?? null);
if (($result['success'] ?? false) === true) {
    $configuredResult = $configurableFormService->saveResponse(
        'ecg_report',
        (int)$request['patient_id'],
        (int)$request['visit_id'],
        'ECG Report',
        $requestId,
        $_POST,
        $currentUser
    );
    if (($configuredResult['success'] ?? false) !== true) {
        $_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
        $_SESSION['validation_errors'] = $configuredResult['errors'] ?? ['Unable to save configured form fields.'];
        header('Location: report.php?id=' . $requestId);
        exit;
    }
}
ecgFlash($result, 'ECG chart and notes saved.');

header('Location: report.php?id=' . $requestId);
exit;
