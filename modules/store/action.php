<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady) {
    http_response_code(503);
    exit('Store tables are not available yet. Apply Migration 031 to enable this section.');
}

storeRequireManageAccess($permissionService, $currentUser);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfVerify($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request.');
}

$itemId = (int)($_POST['id'] ?? 0);
$action = (string)($_POST['action'] ?? '');

$result = match ($action) {
    'activate' => $storeService->activateItem($itemId, $currentUser),
    'deactivate' => $storeService->deactivateItem($itemId, $currentUser),
    default => ['success' => false, 'errors' => ['Invalid item action.']],
};

if (($result['success'] ?? false) !== true) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to complete the item action.'];
    header('Location: view.php?id=' . $itemId);
    exit;
}

$_SESSION['success_message'] = $action === 'activate'
    ? 'Inventory item activated successfully.'
    : 'Inventory item deactivated successfully.';
header('Location: view.php?id=' . $itemId);
exit;

