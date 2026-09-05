<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../history.php');
    exit;
}

requireCsrfToken();

$recordId = (int)($_POST['id'] ?? 0);
$record = $dressingRecordService->getById($recordId, $currentUser);
if (!$record) {
    http_response_code(404);
    exit('Dressing record not found.');
}

$visit = nursingRequireVisit($visitService, (int)$record['visit_id']);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canEditNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('Dressing record editing is denied.');
}

$result = $dressingRecordService->update($recordId, $_POST, $currentUser);
if (($result['success'] ?? false) === true) {
    $configuredResult = $configurableFormService->saveResponse(
        'dressing_record',
        (int)$visit['patient_id'],
        (int)$visit['id'],
        'Dressing Record',
        $recordId,
        $_POST,
        $currentUser
    );
    if (($configuredResult['success'] ?? false) !== true) {
        $_SESSION['validation_errors'] = $configuredResult['errors'] ?? ['Unable to save configured form fields.'];
        $_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
        header('Location: edit.php?id=' . $recordId);
        exit;
    }
    $_SESSION['success_message'] = 'Dressing record updated.';
    header('Location: view.php?id=' . $recordId);
    exit;
}

$_SESSION['old_dressing_record'] = $_POST;
$_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
dressingFlash($result, 'Dressing record updated.');
header('Location: edit.php?id=' . $recordId);
exit;
