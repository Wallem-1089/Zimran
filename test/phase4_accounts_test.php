<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/AccountsService.php';
require_once __DIR__ . '/../services/PermissionService.php';

function assertAccounts(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireAccountsSuccess(array $result, string $label): array
{
    assertAccounts(($result['success'] ?? false) === true, $label . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertAccounts(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Phase 4.1 tests are not isolated from the live database.'
);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL .
    'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/030_phase4_accounts_price_catalogue_up.sql', 30);

$pdo->exec("DELETE FROM audit_logs WHERE module = 'Accounts' AND action LIKE 'BILLABLE_ITEM_%'");
$pdo->exec("DELETE FROM billable_items WHERE item_code LIKE 'ACC-%'");

$users = [];
$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('walter','dev_accounts','dev_doctor','dev_nurse','dev_records')
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}

foreach (['walter', 'dev_accounts', 'dev_doctor', 'dev_nurse', 'dev_records'] as $username) {
    assertAccounts(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['walter'];
$accounts = $users['dev_accounts'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];
$records = $users['dev_records'];

$service = new AccountsService($pdo, null, new PermissionService($pdo));

try {
    foreach ([
        'view_billable_items',
        'create_billable_items',
        'edit_billable_items',
        'manage_billable_item_status',
    ] as $permissionKey) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key AND is_active = 1');
        $stmt->execute([':permission_key' => $permissionKey]);
        assertAccounts((int)$stmt->fetchColumn() === 1, 'Missing permission ' . $permissionKey . '.');
    }

    assertAccounts(in_array('billable_items', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Billable items table is missing.');
    assertAccounts(str_contains(file_get_contents(__DIR__ . '/../layouts/sidebar.php'), '/modules/accounts/index.php'), 'Sidebar missing Accounts destination.');
    assertAccounts(!str_contains(file_get_contents(__DIR__ . '/../modules/visits/partials/workspace_navigation.php'), '/modules/accounts/index.php'), 'Encounter Workspace gained an Accounts tab unexpectedly.');

    assertAccounts((new PermissionService($pdo))->canViewBillableItems($accounts), 'Accountant should be able to view billable items.');
    assertAccounts((new PermissionService($pdo))->canCreateBillableItems($accounts), 'Accountant should be able to create billable items.');
    assertAccounts((new PermissionService($pdo))->canEditBillableItems($accounts), 'Accountant should be able to edit billable items.');
    assertAccounts((new PermissionService($pdo))->canManageBillableItemStatus($accounts), 'Accountant should be able to manage billable item status.');
    assertAccounts((new PermissionService($pdo))->canViewBillableItems($doctor), 'Doctor should be able to view billable items.');
    assertAccounts(!(new PermissionService($pdo))->canCreateBillableItems($doctor), 'Doctor should not be able to create billable items.');

    $serviceItem = requireAccountsSuccess($service->createItem([
        'item_code' => 'ACC-SRV-001',
        'item_name' => 'General Consultation',
        'item_type' => 'Service',
        'department_id' => (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Doctor' LIMIT 1")->fetchColumn(),
        'description' => 'General consultation fee.',
        'unit_price' => 5000,
        'unit' => '',
        'is_active' => 1,
    ], $accounts), 'Create service item');
    $serviceItemId = (int)$serviceItem['billable_item_id'];

    $productItem = requireAccountsSuccess($service->createItem([
        'item_code' => 'ACC-PRD-001',
        'item_name' => 'Amoxicillin 500 mg',
        'item_type' => 'Product',
        'department_id' => null,
        'description' => 'Capsule price catalogue item.',
        'unit_price' => 150,
        'unit' => 'Capsule',
        'is_active' => 1,
    ], $accounts), 'Create product item');
    $productItemId = (int)$productItem['billable_item_id'];

    $duplicate = $service->createItem([
        'item_code' => 'ACC-SRV-001',
        'item_name' => 'Duplicate consultation',
        'item_type' => 'Service',
        'department_id' => null,
        'description' => 'Should fail.',
        'unit_price' => 5000,
        'unit' => null,
        'is_active' => 1,
    ], $accounts);
    assertAccounts(($duplicate['success'] ?? true) === false, 'Duplicate item code was accepted.');

    $listed = $service->listItems(['item_code' => 'ACC-', 'status' => 'active'], $accounts);
    assertAccounts(count($listed) >= 2, 'Filtered item list is incomplete.');

    $viewItem = $service->getItemById($serviceItemId, $doctor);
    assertAccounts($viewItem !== null && (string)$viewItem['item_code'] === 'ACC-SRV-001', 'Created item could not be viewed.');

    $updated = requireAccountsSuccess($service->updateItem($serviceItemId, [
        'item_code' => 'ACC-SRV-001',
        'item_name' => 'General Consultation Updated',
        'item_type' => 'Service',
        'department_id' => (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Doctor' LIMIT 1")->fetchColumn(),
        'description' => 'Updated consultation fee.',
        'unit_price' => 5500,
        'unit' => null,
    ], $accounts), 'Update service item');
    assertAccounts(($updated['success'] ?? false) === true, 'Price catalogue update failed.');

    $doctorDenied = $service->createItem([
        'item_code' => 'ACC-DENIED-001',
        'item_name' => 'Doctor should not create',
        'item_type' => 'Service',
        'department_id' => null,
        'description' => 'Unauthorized.',
        'unit_price' => 10,
        'unit' => null,
        'is_active' => 1,
    ], $doctor);
    assertAccounts(($doctorDenied['success'] ?? true) === false, 'Doctor created a billable item unexpectedly.');

    $nurseDenied = $service->updateItem($productItemId, [
        'item_code' => 'ACC-PRD-001',
        'item_name' => 'Nurse update should fail',
        'item_type' => 'Product',
        'department_id' => null,
        'description' => 'Unauthorized.',
        'unit_price' => 150,
        'unit' => 'Capsule',
    ], $nurse);
    assertAccounts(($nurseDenied['success'] ?? true) === false, 'Nurse updated a billable item unexpectedly.');

    $deactivated = requireAccountsSuccess($service->deactivateItem($productItemId, $accounts), 'Deactivate product item');
    assertAccounts(($deactivated['success'] ?? false) === true, 'Deactivate failed.');

    $reactivated = requireAccountsSuccess($service->activateItem($productItemId, $admin), 'Activate product item as administrator');
    assertAccounts(($reactivated['success'] ?? false) === true, 'Activate failed.');

    $statusList = $service->searchItems(['status' => 'active'], $accounts);
    assertAccounts(count($statusList) >= 2, 'Active item search is incomplete.');

    $auditCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM audit_logs
        WHERE module = 'Accounts'
          AND action IN (
              'BILLABLE_ITEM_CREATED',
              'BILLABLE_ITEM_UPDATED',
              'BILLABLE_ITEM_ACTIVATED',
              'BILLABLE_ITEM_DEACTIVATED'
          )
    ")->fetchColumn();
    assertAccounts($auditCount >= 4, 'Accounts audit entries were not written.');

    echo 'PASS: Phase 4.1 Accounts / Price Catalogue regression passed.' . PHP_EOL;
} finally {
    $pdo->exec("DELETE FROM billable_items WHERE item_code LIKE 'ACC-%'");
    $pdo->exec("DELETE FROM audit_logs WHERE module = 'Accounts' AND action LIKE 'BILLABLE_ITEM_%'");
}
