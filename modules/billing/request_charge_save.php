<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

if (!$billingTablesReady || !$billingRequestsReady) {
    http_response_code(503);
    exit('Billing request tables are not available yet. Apply Migration 044 to enable this section.');
}

$requestId = (int)($_POST['billing_request_id'] ?? 0);
$request = $billingService->getBillingRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Billing request not found.');
}

$result = $billingService->chargeBillingRequest($_POST, $currentUser);
$_SESSION['success_message'] = $result['success'] ? 'Billing request converted to patient charge.' : null;
$_SESSION['error_message'] = $result['success'] ? null : implode(' ', (array)($result['errors'] ?? ['Unable to convert billing request.']));
$_SESSION['validation_errors'] = $result['success'] ? [] : (array)($result['errors'] ?? []);

header('Location: ' . ($result['success'] ? 'view.php?visit=' . (int)$request['visit_id'] : 'request_review.php?id=' . $requestId));
exit;
