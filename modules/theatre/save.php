<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$visitId = (int)($_POST['visit_id'] ?? 0);
$result = $theatreService->create($_POST, $currentUser);
theatreFlash($result, 'Theatre draft created.');

if (($result['success'] ?? false) === true) {
    $configuredResult = $configurableFormService->saveResponse(
        'theatre_record',
        (int)($result['patient_id'] ?? 0),
        (int)($result['visit_id'] ?? $visitId),
        'Theatre Record',
        (int)$result['theatre_record_id'],
        $_POST,
        $currentUser
    );
    if (($configuredResult['success'] ?? false) !== true) {
        $_SESSION['validation_errors'] = $configuredResult['errors'] ?? ['Unable to save configured form fields.'];
        $_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
        header('Location: edit.php?id=' . (int)$result['theatre_record_id']);
        exit;
    }
    header('Location: view.php?id=' . (int)$result['theatre_record_id']);
    exit;
}

$_SESSION['old_theatre'] = $_POST;
$_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
header('Location: create.php?visit=' . $visitId);
exit;
