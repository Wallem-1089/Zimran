<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admissionRequireReady($admissionTablesReady);
requireCsrfToken();

$visitId = (int)($_POST['visit_id'] ?? 0);
$result = $admissionService->admit($_POST, $currentUser);
admissionFlash($result, 'Patient admitted.');

if (($result['success'] ?? false) === true) {
    $configuredResult = $configurableFormService->saveResponse(
        'admission_record',
        (int)($_POST['patient_id'] ?? 0),
        $visitId,
        'Admission Record',
        (int)$result['admission_id'],
        $_POST,
        $currentUser
    );
    if (($configuredResult['success'] ?? false) !== true) {
        $_SESSION['validation_errors'] = $configuredResult['errors'] ?? ['Unable to save configured form fields.'];
        $_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
        header('Location: view.php?id=' . (int)$result['admission_id']);
        exit;
    }
    header('Location: view.php?id=' . (int)$result['admission_id']);
    exit;
}

$_SESSION['old_admission'] = $_POST;
$_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
header('Location: create.php?visit=' . $visitId);
exit;
