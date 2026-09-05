<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$visitId = (int)($_POST['visit_id'] ?? 0);
$visit = nursingRequireVisit($visitService, $visitId);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canCreateNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('Nursing creation is denied.');
}

$result = $nursingService->create($_POST, $currentUser);
if (($result['success'] ?? false) === true) {
    $configuredResult = $configurableFormService->saveResponse(
        'nursing_assessment',
        (int)$visit['patient_id'],
        (int)$visit['id'],
        'Nursing Assessment',
        (int)$result['nursing_assessment_id'],
        $_POST,
        $currentUser
    );
    if (($configuredResult['success'] ?? false) !== true) {
        $_SESSION['validation_errors'] = $configuredResult['errors'] ?? ['Unable to save configured form fields.'];
        $_SESSION['old_nursing_assessment'] = $_POST;
        $_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
        header('Location: edit.php?id=' . (int)$result['nursing_assessment_id']);
        exit;
    }
    $_SESSION['success_message'] = 'Nursing assessment saved.';
    header('Location: view.php?id=' . (int)$result['nursing_assessment_id']);
    exit;
}

$_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to save nursing assessment.'];
$_SESSION['old_nursing_assessment'] = $_POST;
$_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
$_SESSION['error_message'] = 'Unable to save nursing assessment.';
header('Location: create.php?visit=' . $visitId);
exit;
