<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: history.php');
    exit;
}

requireCsrfToken();

$recordId = (int)($_POST['id'] ?? 0);
$record = $medicationAdministrationService->getById($recordId, $currentUser);
if (!$record) {
    http_response_code(404);
    exit('Drug chart entry not found.');
}

$visit = nursingRequireVisit($visitService, (int)$record['visit_id']);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canEditNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('Drug chart entry edit is denied.');
}

$result = $medicationAdministrationService->update($recordId, $_POST, $currentUser);
if (($result['success'] ?? false) === true) {
    $_SESSION['success_message'] = 'Drug chart entry updated.';
    header('Location: view.php?id=' . $recordId);
    exit;
}

$_SESSION['old_drug_chart'] = $_POST;
drugChartFlash($result, 'Drug chart entry updated.');
header('Location: edit.php?id=' . $recordId);
exit;
