<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady) {
    http_response_code(503);
    exit('Store tables are not available yet. Apply Migration 031 to enable this section.');
}

storeRequireManageAccess($permissionService, $currentUser);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

if (!csrfVerify($_POST['csrf_token'] ?? null)) {
    $_SESSION['validation_errors'] = ['Invalid CSRF token.'];
    $_SESSION['old_store_item'] = $_POST;
    header('Location: create.php');
    exit;
}

$result = $storeService->createItem($_POST, $currentUser);
if (($result['success'] ?? false) !== true) {
    $_SESSION['old_store_item'] = $_POST;
    storeFlash($result, 'Inventory item created successfully.');
    header('Location: create.php');
    exit;
}

storeFlash($result, 'Inventory item created successfully.');
header('Location: view.php?id=' . (int)$result['inventory_item_id']);
exit;

