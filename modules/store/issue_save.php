<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady) {
    http_response_code(503);
    exit('Store tables are not available yet. Apply Migration 031 to enable this section.');
}

storeRequireMovementAccess($permissionService, $currentUser, 'issue');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!csrfVerify($_POST['csrf_token'] ?? null)) {
    $_SESSION['validation_errors'] = ['Invalid CSRF token.'];
    $_SESSION['old_store_movement'] = $_POST;
    header('Location: issue.php?id=' . (int)($_POST['inventory_item_id'] ?? 0));
    exit;
}

$result = $storeService->issueStock($_POST, $currentUser);
if (($result['success'] ?? false) !== true) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to issue stock.'];
    $_SESSION['old_store_movement'] = $_POST;
    header('Location: issue.php?id=' . (int)($_POST['inventory_item_id'] ?? 0));
    exit;
}

$_SESSION['success_message'] = 'Stock issued successfully.';
header('Location: view.php?id=' . (int)$result['inventory_item_id']);
exit;

