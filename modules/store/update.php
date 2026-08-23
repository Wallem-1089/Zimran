<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady) {
    http_response_code(503);
    exit('Store tables are not available yet. Apply Migration 031 to enable this section.');
}

storeRequireManageAccess($permissionService, $currentUser);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['validation_errors'] = ['Invalid CSRF token.'];
    $_SESSION['old_store_item'] = $_POST;
    header('Location: edit.php?id=' . (int)($_POST['id'] ?? 0));
    exit;
}

$itemId = (int)($_POST['id'] ?? 0);
$result = $storeService->updateItem($itemId, $_POST, $currentUser);

if (($result['success'] ?? false) !== true) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to update inventory item.'];
    $_SESSION['old_store_item'] = $_POST;
    header('Location: edit.php?id=' . $itemId);
    exit;
}

$_SESSION['success_message'] = 'Inventory item updated successfully.';
header('Location: view.php?id=' . $itemId);
exit;
