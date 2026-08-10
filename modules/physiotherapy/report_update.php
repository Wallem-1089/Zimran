<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$sessionId = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT) ?: 0;
if (!$sessionId) {
    exit('Invalid physiotherapy session.');
}

$session = $physiotherapyService->getSessionById($sessionId, $currentUser);
if (!$session) {
    exit('Physiotherapy session not found.');
}

$record = $physiotherapyService->getRecordById((int)$session['physiotherapy_record_id'], $currentUser);
if (!$record) {
    exit('Physiotherapy record not found.');
}

$visit = physiotherapyRequireVisit($visitService, (int)$record['visit_id']);
if (!$permissionService->canManagePhysiotherapySessions($visit, $currentUser)) {
    exit('You cannot manage this physiotherapy session.');
}

$result = $physiotherapyService->updateSession($sessionId, $_POST, $currentUser);
physiotherapyFlash($result, 'Physiotherapy session updated.');
header('Location: view.php?id=' . (int)$record['id']);
exit;
