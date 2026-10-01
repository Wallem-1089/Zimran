<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

if (!$billingTablesReady) {
    http_response_code(503);
    exit('Billing tables are not available yet. Apply Migration 033 to enable this section.');
}

$visitId = (int)($_POST['visit_id'] ?? 0);
$visit = $visitService->getVisitById($visitId);
if (!$visit) {
    http_response_code(404);
    exit('Encounter not found.');
}

$activeDepartmentName = (string)($currentUser['active_department_name'] ?? $currentUser['department_name'] ?? '');
if (strcasecmp($activeDepartmentName, 'Accounts') === 0 && !$permissionService->isAdministrator($currentUser)) {
    http_response_code(403);
    exit('Accounts charges are created automatically from billing requests.');
}

$result = $billingService->createCharge($_POST, $currentUser);
$_SESSION['success_message'] = $result['success'] ? 'Patient charge saved.' : null;
$_SESSION['error_message'] = $result['success'] ? null : implode(' ', (array)($result['errors'] ?? ['Unable to save patient charge.']));
$_SESSION['validation_errors'] = $result['success'] ? [] : (array)($result['errors'] ?? []);

header('Location: view.php?visit=' . $visitId);
exit;
