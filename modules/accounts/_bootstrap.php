<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/AccountsService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

function accountsTableExists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table'
        );
        $stmt->execute([':table' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

function accountsFlash(array $result, string $successMessage): void
{
    if (($result['success'] ?? false) === true) {
        $_SESSION['success_message'] = $successMessage;
        return;
    }

    $_SESSION['validation_errors'] = $result['errors'] ?? ['The Accounts action failed.'];
}

function accountsBackToIndex(): string
{
    return 'index.php';
}

function accountsRequireAccess(PermissionService $permissionService, array $currentUser): void
{
    if (!$permissionService->canViewBillableItems($currentUser)) {
        http_response_code(403);
        exit('Accounts access denied.');
    }
}

function accountsDepartments(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, department_name
        FROM departments
        WHERE is_active = 1
        ORDER BY department_name ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$permissionService = new PermissionService($pdo);
$accountsService = new AccountsService($pdo, null, $permissionService);
$accountsTablesReady = accountsTableExists($pdo, 'billable_items');
$accountsDepartmentOptions = accountsDepartments($pdo);

