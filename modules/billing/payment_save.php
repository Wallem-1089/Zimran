<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

if (!$billingTablesReady) {
    http_response_code(503);
    exit('Billing tables are not available yet. Apply Migration 033 to enable this section.');
}

$invoiceId = (int)($_POST['invoice_id'] ?? 0);
$invoice = $billingService->getInvoiceById($invoiceId, $currentUser);
if (!$invoice) {
    http_response_code(404);
    exit('Invoice not found.');
}

$visitId = (int)($invoice['visit_id'] ?? 0);
$result = $billingService->recordPayment($_POST, $currentUser);
$_SESSION['success_message'] = $result['success'] ? 'Payment recorded.' : null;
$_SESSION['error_message'] = $result['success'] ? null : implode(' ', (array)($result['errors'] ?? ['Unable to record payment.']));
$_SESSION['validation_errors'] = $result['success'] ? [] : (array)($result['errors'] ?? []);

header('Location: view.php?visit=' . $visitId);
exit;
