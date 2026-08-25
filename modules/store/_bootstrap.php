<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/AuditService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/PatientStockUsageService.php';
require_once __DIR__ . '/../../services/StoreService.php';

function storeTableExists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :table
        ');
        $stmt->execute([':table' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

function storeFlash(array $result, string $successMessage): void
{
    if (($result['success'] ?? false) === true) {
        $_SESSION['success_message'] = $successMessage;
        return;
    }

    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to complete the store action.'];
}

function storeDepartments(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT id, department_name
        FROM departments
        WHERE is_active = 1
        ORDER BY department_name ASC
    ');

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function storeBillableItems(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT id, item_code, item_name, unit_price
        FROM billable_items
        WHERE is_active = 1
        ORDER BY item_name ASC
    ');

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function storeBackToIndex(): string
{
    return 'index.php';
}

function storeBackToView(int $itemId): string
{
    return 'view.php?id=' . $itemId;
}

function storeBackToLedger(int $itemId): string
{
    return 'ledger.php?id=' . $itemId;
}

function storeRequireAccess(PermissionService $permissionService, array $currentUser): void
{
    if (!$permissionService->canViewInventory($currentUser)) {
        http_response_code(403);
        exit('Store access denied.');
    }
}

function storeRequireManageAccess(PermissionService $permissionService, array $currentUser): void
{
    if (!$permissionService->canManageInventoryItems($currentUser)) {
        http_response_code(403);
        exit('Store management access denied.');
    }
}

function storeRequireMovementAccess(PermissionService $permissionService, array $currentUser, string $permission): void
{
    $allowed = match ($permission) {
        'receive' => $permissionService->canReceiveStock($currentUser),
        'issue' => $permissionService->canIssueStock($currentUser),
        'return' => $permissionService->canReturnStock($currentUser),
        'adjust' => $permissionService->canAdjustStock($currentUser),
        default => false,
    };

    if (!$allowed) {
        http_response_code(403);
        exit('Store stock access denied.');
    }
}

function storeRequireExternalSalesAccess(PermissionService $permissionService, array $currentUser): void
{
    if (!$permissionService->canViewExternalSales($currentUser)) {
        http_response_code(403);
        exit('External sales access denied.');
    }
}

function storeRequireCreateExternalSaleAccess(PermissionService $permissionService, array $currentUser): void
{
    if (!$permissionService->canCreateExternalSale($currentUser)) {
        http_response_code(403);
        exit('External sale creation denied.');
    }
}

function storeRequireExternalSaleReceiptAccess(PermissionService $permissionService, array $currentUser): void
{
    if (!$permissionService->canViewExternalSaleReceipts($currentUser)) {
        http_response_code(403);
        exit('External sale receipt access denied.');
    }
}

function storeRequireItem(StoreService $storeService, int $itemId, array $currentUser): array
{
    $item = $storeService->getItemById($itemId, $currentUser);
    if (!$item) {
        http_response_code(404);
        exit('Inventory item not found.');
    }

    return $item;
}

function storeStoreDepartmentId(PDO $pdo): ?int
{
    $stmt = $pdo->query("
        SELECT id
        FROM departments
        WHERE department_name = 'Store'
        LIMIT 1
    ");
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

$permissionService = new PermissionService($pdo);
$storeService = new StoreService($pdo, null, $permissionService);
$patientStockUsageService = new PatientStockUsageService($pdo, $storeService, null, null, $permissionService);
$storeTablesReady = storeTableExists($pdo, 'inventory_items')
    && storeTableExists($pdo, 'stock_transactions')
    && storeTableExists($pdo, 'department_stock_balances');
$patientStockUsageTablesReady = storeTableExists($pdo, 'patient_stock_usage');
$storeExternalSalesReady = storeTableExists($pdo, 'external_sales')
    && storeTableExists($pdo, 'external_sale_items');
$storeDepartmentOptions = storeDepartments($pdo);
$storeBillableItemOptions = storeBillableItems($pdo);
