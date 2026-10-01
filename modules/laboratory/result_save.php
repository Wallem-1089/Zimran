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
if (!$permissionService->canEnterLaboratoryResult($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot enter this laboratory result.');
}

$result = $laboratoryService->saveResult($_POST, $currentUser);
if (($result['success'] ?? false) === true) {
    $uploadResult = $diagnosticAttachmentService->uploadMany(
        'Laboratory',
        $requestId,
        [
            'visit_id' => (int)$request['visit_id'],
            'patient_id' => (int)$request['patient_id'],
        ],
        $_FILES['diagnostic_attachments'] ?? null,
        $currentUser
    );
    if (empty($uploadResult['success'])) {
        $_SESSION['validation_errors'] = $uploadResult['errors'] ?? ['Unable to upload laboratory attachments.'];
        header('Location: result.php?id=' . $requestId);
        exit;
    }
    if ((int)($uploadResult['uploaded'] ?? 0) > 0) {
        $_SESSION['success_message'] = 'Laboratory result saved. Uploaded ' . (int)$uploadResult['uploaded'] . ' attachment(s).';
        header('Location: result.php?id=' . $requestId);
        exit;
    }
}
laboratoryFlash($result, 'Laboratory result saved.');

header('Location: result.php?id=' . $requestId);
exit;
