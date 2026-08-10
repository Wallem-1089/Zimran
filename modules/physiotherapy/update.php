<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recordId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$recordId) {
    header('Location: index.php');
    exit;
}

$record = $physiotherapyService->getRecordById($recordId, $currentUser);
if (!$record) {
    http_response_code(404);
    exit('Physiotherapy record not found.');
}

$visit = physiotherapyRequireVisit($visitService, (int)$record['visit_id']);
if (!$permissionService->canEditPhysiotherapy($visit, $currentUser)) {
    http_response_code(403);
    exit('You cannot edit this physiotherapy record.');
}

if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)
    && !$permissionService->isAdministrator($currentUser)) {
    http_response_code(403);
    exit('Completed or cancelled encounters are read-only.');
}

$result = $physiotherapyService->updateRecord($recordId, $_POST, $currentUser);
if (($result['success'] ?? false) !== true) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to update physiotherapy record.'];
    header('Location: edit.php?id=' . $recordId);
    exit;
}

$_SESSION['success_message'] = 'Physiotherapy record updated.';
header('Location: view.php?id=' . $recordId);
exit;
