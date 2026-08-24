<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

if (!$billingTablesReady || !$billingRequestsReady) {
    http_response_code(503);
    exit('Billing request tables are not available yet. Apply Migration 044 to enable this section.');
}

$visitId = (int)($_POST['visit_id'] ?? 0);
$visit = $visitService->getVisitById($visitId);
if (!$visit) {
    http_response_code(404);
    exit('Encounter not found.');
}

$result = $billingService->createBillingRequest($_POST, $currentUser);
$_SESSION['success_message'] = $result['success'] ? 'Billing request submitted for Accounts review.' : null;
$_SESSION['error_message'] = $result['success'] ? null : implode(' ', (array)($result['errors'] ?? ['Unable to submit billing request.']));
$_SESSION['validation_errors'] = $result['success'] ? [] : (array)($result['errors'] ?? []);

header('Location: view.php?visit=' . $visitId);
exit;
