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
if (!$permissionService->canEditRadiologyResult($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot edit this radiology result.');
}

$result = $radiologyService->updateResult($_POST, $currentUser, null);
if (($result['success'] ?? false) === true) {
    $uploadResult = $diagnosticAttachmentService->uploadMany(
        'Radiology',
        $requestId,
        [
            'visit_id' => (int)$request['visit_id'],
            'patient_id' => (int)$request['patient_id'],
        ],
        $_FILES['diagnostic_attachments'] ?? null,
        $currentUser
    );
    if (empty($uploadResult['success'])) {
        $_SESSION['validation_errors'] = $uploadResult['errors'] ?? ['Unable to upload radiology attachments.'];
        header('Location: report.php?id=' . $requestId);
        exit;
    }
    if ((int)($uploadResult['uploaded'] ?? 0) > 0) {
        $_SESSION['success_message'] = 'Radiology report updated. Uploaded ' . (int)$uploadResult['uploaded'] . ' attachment(s).';
        header('Location: view.php?id=' . $requestId);
        exit;
    }
}
radiologyFlash($result, 'Radiology report updated.');

header('Location: ' . (($result['success'] ?? false) ? 'view.php' : 'report.php') . '?id=' . $requestId);
exit;

