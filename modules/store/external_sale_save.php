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

storeRequireCreateExternalSaleAccess($permissionService, $currentUser);

$result = $storeService->createExternalSale($_POST, $currentUser);

if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to create external sale.'];
    $_SESSION['old_input'] = $_POST;
    header('Location: external_sale_create.php');
    exit;
}

$_SESSION['success_message'] = 'External sale completed successfully.';
header('Location: external_sale_view.php?id=' . (int)$result['external_sale_id']);
exit;
