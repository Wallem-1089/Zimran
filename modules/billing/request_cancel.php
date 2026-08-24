<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

if (!$billingTablesReady || !$billingRequestsReady) {
    http_response_code(503);
    exit('Billing request tables are not available yet. Apply Migration 044 to enable this section.');
}

$requestId = (int)($_POST['billing_request_id'] ?? 0);
$visitId = (int)($_POST['visit_id'] ?? 0);
$result = $billingService->cancelBillingRequest($requestId, (string)($_POST['reason'] ?? ''), $currentUser);
$_SESSION['success_message'] = $result['success'] ? 'Billing request cancelled.' : null;
$_SESSION['error_message'] = $result['success'] ? null : implode(' ', (array)($result['errors'] ?? ['Unable to cancel billing request.']));
$_SESSION['validation_errors'] = $result['success'] ? [] : (array)($result['errors'] ?? []);

header('Location: ' . ($visitId > 0 ? 'view.php?visit=' . $visitId : 'billing_requests.php'));
exit;
