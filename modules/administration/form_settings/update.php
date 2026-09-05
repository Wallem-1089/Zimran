<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$formKey = trim((string)($_POST['form_key'] ?? ''));
$result = $configurableFormService->saveFieldConfig($formKey, $_POST, $currentUser);

if (($result['success'] ?? false) === true) {
    $_SESSION['success_message'] = 'Form settings updated.';
    header('Location: edit.php?form=' . urlencode($formKey));
    exit;
}

$_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to update form settings.'];
header('Location: edit.php?form=' . urlencode($formKey));
exit;
