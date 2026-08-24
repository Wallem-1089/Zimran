<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: external_sales.php');
    exit;
}

requireCsrfToken();

if (!$storeTablesReady || !$storeExternalSalesReady) {
    http_response_code(503);
    exit('External sales tables are not available yet. Apply Migration 043 to enable this section.');
}

$saleId = filter_input(INPUT_POST, 'external_sale_id', FILTER_VALIDATE_INT) ?: 0;
$reason = trim((string)($_POST['cancel_reason'] ?? ''));

$result = $storeService->cancelExternalSale($saleId, $reason, $currentUser);

if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to cancel external sale.'];
    header('Location: external_sale_view.php?id=' . $saleId . '#cancel-sale');
    exit;
}

$_SESSION['success_message'] = 'External sale cancelled.';
header('Location: external_sale_view.php?id=' . $saleId);
exit;
