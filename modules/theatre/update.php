<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$recordId = (int)($_POST['id'] ?? 0);
$existing = $theatreService->getById($recordId, $currentUser);
$result = $theatreService->update($recordId, $_POST, $currentUser);
theatreFlash($result, 'Theatre record updated.');

if (($result['success'] ?? false) === true) {
    $configuredResult = $configurableFormService->saveResponse(
        'theatre_record',
        (int)($existing['patient_id'] ?? 0),
        (int)($existing['visit_id'] ?? ($_POST['visit_id'] ?? 0)),
        'Theatre Record',
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
    header('Location: view.php?id=' . $recordId);
    exit;
}

$visitId = (int)($_POST['visit_id'] ?? 0);
$_SESSION['old_theatre'] = $_POST;
$_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
header('Location: edit.php?id=' . $recordId);
exit;
