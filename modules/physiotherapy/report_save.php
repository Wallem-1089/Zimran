<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$recordId = filter_input(INPUT_POST, 'physiotherapy_record_id', FILTER_VALIDATE_INT) ?: 0;
if (!$recordId) {
    exit('Invalid physiotherapy record.');
}

$record = $physiotherapyService->getRecordById($recordId, $currentUser);
if (!$record) {
    exit('Physiotherapy record not found.');
}

$visit = physiotherapyRequireVisit($visitService, (int)$record['visit_id']);
if (!$permissionService->canManagePhysiotherapySessions($visit, $currentUser)) {
    exit('You cannot manage this physiotherapy session.');
}

$result = $physiotherapyService->addSession($_POST, $currentUser);
physiotherapyFlash($result, 'Physiotherapy session saved.');
header('Location: view.php?id=' . $recordId);
exit;
