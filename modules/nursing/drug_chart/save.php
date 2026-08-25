<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../history.php');
    exit;
}

requireCsrfToken();

$visitId = (int)($_POST['visit_id'] ?? 0);
$visit = nursingRequireVisit($visitService, $visitId);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canCreateNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('Drug chart entry creation is denied.');
}

$result = $medicationAdministrationService->create($_POST, $currentUser);
if (($result['success'] ?? false) === true) {
    $_SESSION['success_message'] = 'Drug chart entry saved.';
    header('Location: view.php?id=' . (int)$result['medication_administration_id']);
    exit;
}

$_SESSION['old_drug_chart'] = $_POST;
drugChartFlash($result, 'Drug chart entry saved.');
header('Location: create.php?visit=' . $visitId);
exit;
