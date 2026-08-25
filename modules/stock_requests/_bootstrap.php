<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/StockRequestService.php';
require_once __DIR__ . '/../../services/StoreService.php';

$permissionService = new PermissionService($pdo);
$storeService = new StoreService($pdo, null, $permissionService);
$stockRequestService = new StockRequestService($pdo, null, $permissionService, $storeService);

function stockRequestTableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = :table
    ');
    $stmt->execute([':table' => $table]);
    return (int)$stmt->fetchColumn() > 0;
}

$stockRequestTablesReady = stockRequestTableExists($pdo, 'stock_requests')
    && stockRequestTableExists($pdo, 'stock_request_items');

function stockRequestRequireReady(bool $ready): void
{
    if (!$ready) {
        http_response_code(503);
        exit('Stock Request tables are not available yet. Apply Migration 050 to enable this section.');
    }
}

function stockRequestRequireView(PermissionService $permissionService, array $user): void
{
    if (!$permissionService->canViewStockRequests($user)) {
        http_response_code(403);
        exit('Stock Request access denied.');
    }
}

function stockRequestFlash(array $result, string $successMessage): void
{
    if (($result['success'] ?? false) === true) {
        $_SESSION['success_message'] = $successMessage;
        return;
    }

    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to complete stock request action.'];
}

function stockRequestDepartments(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT id, department_name
        FROM departments
        WHERE is_active = 1
        ORDER BY department_name ASC
    ');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function stockRequestInventoryItems(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT id, item_code, item_name, unit
        FROM inventory_items
        WHERE is_active = 1
        ORDER BY item_name ASC
    ');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function stockRequestBackToIndex(): string
{
    return 'index.php';
}
