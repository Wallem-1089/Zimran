<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: history.php');
    exit;
}

requireCsrfToken();

$recordId = (int)($_POST['id'] ?? 0);
$record = $diabetesMonitoringService->getById($recordId, $currentUser);
if (!$record) {
    http_response_code(404);
    exit('DM Sheet entry not found.');
}

$visit = nursingRequireVisit($visitService, (int)$record['visit_id']);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canEditNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('DM Sheet entry edit is denied.');
}

$result = $diabetesMonitoringService->update($recordId, $_POST, $currentUser);
if (($result['success'] ?? false) === true) {
    $_SESSION['success_message'] = 'DM Sheet entry updated.';
    header('Location: view.php?id=' . $recordId);
    exit;
}

$_SESSION['old_dm_sheet'] = $_POST;
dmSheetFlash($result, 'DM Sheet entry updated.');
header('Location: edit.php?id=' . $recordId);
exit;
