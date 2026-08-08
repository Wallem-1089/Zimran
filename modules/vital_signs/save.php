<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$visitId = (int)($_POST['visit_id'] ?? 0);
$visit = vitalSignsRequireVisit($visitService, $visitId);

if (!$permissionService->canCreateVitalSigns($visit, $currentUser)) {
    http_response_code(403);
    exit('Vital signs creation is denied.');
}

$result = $vitalSignsService->create($_POST, $currentUser);

if ($result['success'] ?? false) {
    $_SESSION['success_message'] = 'Vital signs recorded.';
    header('Location: view.php?id=' . (int)$result['vital_signs_id']);
    exit;
}

$_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to save vital signs.'];
$_SESSION['old_vital_signs'] = $_POST;
$_SESSION['error_message'] = 'Unable to save vital signs.';
header('Location: create.php?visit=' . $visitId);
exit;
