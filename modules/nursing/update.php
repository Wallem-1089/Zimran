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

if (!$permissionService->canEditNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('Nursing editing is denied.');
}

$result = $nursingService->update($assessmentId, $_POST, $currentUser);
if (($result['success'] ?? false) === true) {
    $configuredResult = $configurableFormService->saveResponse(
        'nursing_assessment',
        (int)$visit['patient_id'],
        (int)$visit['id'],
        'Nursing Assessment',
        $assessmentId,
        $_POST,
        $currentUser
    );
    if (($configuredResult['success'] ?? false) !== true) {
        $_SESSION['validation_errors'] = $configuredResult['errors'] ?? ['Unable to save configured form fields.'];
        $_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
        header('Location: edit.php?id=' . $assessmentId);
        exit;
    }
    $_SESSION['success_message'] = 'Nursing assessment updated.';
    header('Location: view.php?id=' . $assessmentId);
    exit;
}

$_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to update nursing assessment.'];
$_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
$_SESSION['error_message'] = 'Unable to update nursing assessment.';
header('Location: edit.php?id=' . $assessmentId);
exit;
