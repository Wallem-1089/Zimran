<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

if (!$billingTablesReady || !$billingDiscountsReady) {
    http_response_code(503);
    exit('Billing discount tables are not available yet. Apply Migration 072 to enable discounts.');
}

$discountId = (int)($_POST['discount_id'] ?? 0);
$visitId = (int)($_POST['visit_id'] ?? 0);
$reason = (string)($_POST['reason'] ?? '');

$result = $billingService->cancelBillingDiscount($discountId, $reason, $currentUser);

$_SESSION['success_message'] = $result['success'] ? 'Billing discount cancelled.' : null;
$_SESSION['error_message'] = $result['success'] ? null : implode(' ', (array)($result['errors'] ?? ['Unable to cancel billing discount.']));
$_SESSION['validation_errors'] = $result['success'] ? [] : (array)($result['errors'] ?? []);

header('Location: view.php?visit=' . $visitId);
exit;
