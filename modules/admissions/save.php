<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admissionRequireReady($admissionTablesReady);
requireCsrfToken();

$visitId = (int)($_POST['visit_id'] ?? 0);
$result = $admissionService->admit($_POST, $currentUser);
admissionFlash($result, 'Patient admitted.');

if (($result['success'] ?? false) === true) {
    header('Location: view.php?id=' . (int)$result['admission_id']);
    exit;
}

$_SESSION['old_admission'] = $_POST;
header('Location: create.php?visit=' . $visitId);
exit;
