<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: history.php');
    exit;
}

requireCsrfToken();

$visitId = (int)($_POST['visit_id'] ?? 0);
$visit = nursingRequireVisit($visitService, $visitId);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canCreateNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('DM Sheet entry creation is denied.');
}

$result = $diabetesMonitoringService->create($_POST, $currentUser);
if (($result['success'] ?? false) === true) {
    $_SESSION['success_message'] = 'DM Sheet entry saved.';
    header('Location: view.php?id=' . (int)$result['diabetes_monitoring_id']);
    exit;
}

$_SESSION['old_dm_sheet'] = $_POST;
dmSheetFlash($result, 'DM Sheet entry saved.');
header('Location: create.php?visit=' . $visitId);
exit;
