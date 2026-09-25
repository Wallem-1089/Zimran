<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

if (!$billingTablesReady || !$billingDiscountsReady) {
    http_response_code(503);
    exit('Billing discount tables are not available yet. Apply Migration 072 to enable discounts.');
}

$visitId = (int)($_POST['visit_id'] ?? 0);
$result = $billingService->applyBillingDiscount($_POST, $currentUser);

$_SESSION['success_message'] = $result['success'] ? 'Billing discount applied.' : null;
$_SESSION['error_message'] = $result['success'] ? null : implode(' ', (array)($result['errors'] ?? ['Unable to apply billing discount.']));
$_SESSION['validation_errors'] = $result['success'] ? [] : (array)($result['errors'] ?? []);

header('Location: ' . ($result['success'] ? 'view.php?visit=' . $visitId : 'discount_create.php?visit=' . $visitId));
exit;
