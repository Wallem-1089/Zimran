<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$vitalSignsId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$existing = $vitalSignsService->getById($vitalSignsId);
if (!$existing) {
    http_response_code(404);
    exit('Vital signs record not found.');
}

$visit = vitalSignsRequireVisit($visitService, (int)$existing['visit_id']);
if (!$permissionService->canEditVitalSigns($visit, $currentUser)) {
    http_response_code(403);
    exit('Vital signs editing is denied.');
}

$result = $vitalSignsService->update($vitalSignsId, $_POST, $currentUser);

if ($result['success'] ?? false) {
    $_SESSION['success_message'] = 'Vital signs updated.';
    header('Location: view.php?id=' . $vitalSignsId);
    exit;
}

$_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to update vital signs.'];
$_SESSION['error_message'] = 'Unable to update vital signs.';
header('Location: edit.php?id=' . $vitalSignsId);
exit;
