<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/StoreService.php';

function assertStore(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function requireStoreSuccess(array $result, string $label): array
{
    assertStore(($result['success'] ?? false) === true, $label . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertStore(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Phase 4.2 tests are not isolated from the live database.'
);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL .
    'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$manager->apply(__DIR__ . '/../database/migrations/030_phase4_accounts_price_catalogue_up.sql', 30);
$manager->apply(__DIR__ . '/../database/migrations/031_phase4_store_inventory_up.sql', 31);

$pdo->exec("DELETE FROM audit_logs WHERE module = 'Store' AND (action LIKE 'INVENTORY_ITEM_%' OR action LIKE 'STOCK_%')");
$pdo->exec("DELETE FROM stock_transactions WHERE inventory_item_id IN (SELECT id FROM inventory_items WHERE item_code LIKE 'STO-%')");
$pdo->exec("DELETE FROM department_stock_balances WHERE inventory_item_id IN (SELECT id FROM inventory_items WHERE item_code LIKE 'STO-%')");
$pdo->exec("DELETE FROM inventory_items WHERE item_code LIKE 'STO-%'");
$pdo->exec("DELETE FROM billable_items WHERE item_code LIKE 'STB-%'");
$pdo->exec("DELETE FROM user_departments WHERE user_id IN (SELECT id FROM users WHERE username = 'dev_store')");
$pdo->exec("DELETE FROM users WHERE username = 'dev_store'");

$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('admin','dev_accounts','dev_doctor','dev_nurse','dev_records')
")->fetchAll(PDO::FETCH_ASSOC);
$users = [];
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}
foreach (['admin', 'dev_accounts', 'dev_doctor', 'dev_nurse', 'dev_records'] as $username) {
    assertStore(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$admin = $users['admin'];
$accounts = $users['dev_accounts'];
$doctor = $users['dev_doctor'];
$nurse = $users['dev_nurse'];
$records = $users['dev_records'];

$storeRoleId = (int)$pdo->query("SELECT id FROM roles WHERE role_name = 'Store Officer' LIMIT 1")->fetchColumn();
$storeDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Store' LIMIT 1")->fetchColumn();
$pharmacyDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Pharmacy' LIMIT 1")->fetchColumn();
$theatreDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Theatre' LIMIT 1")->fetchColumn();
$laboratoryDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Laboratory' LIMIT 1")->fetchColumn();

assertStore($storeRoleId > 0 && $storeDepartmentId > 0, 'Store role or department is missing.');

$pdo->prepare('
    INSERT INTO users (
        employee_id, first_name, last_name, gender, phone, email, username, password,
        department_id, role_id, status, created_at
    ) VALUES (
        :employee_id, :first_name, :last_name, :gender, :phone, :email, :username, :password,
        :department_id, :role_id, \'Active\', NOW()
    )
')->execute([
    ':employee_id' => 'DEV-STO-001',
    ':first_name' => 'Dev',
    ':last_name' => 'Store',
    ':gender' => null,
    ':phone' => null,
    ':email' => 'dev.store@example.com',
    ':username' => 'dev_store',
    ':password' => password_hash('store1234', PASSWORD_DEFAULT),
    ':department_id' => $storeDepartmentId,
    ':role_id' => $storeRoleId,
]);

$store = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username = 'dev_store'
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
assertStore((bool)$store, 'Missing store fixture user.');

$pdo->prepare('INSERT INTO user_departments (user_id, department_id, is_primary, is_active, assigned_by) VALUES (:user_id, :department_id, 1, 1, :assigned_by)')
    ->execute([
        ':user_id' => (int)$store['id'],
        ':department_id' => $storeDepartmentId,
        ':assigned_by' => (int)$admin['id'],
    ]);

$service = new StoreService($pdo, null, new PermissionService($pdo));

try {
    foreach ([
        'view_inventory',
        'manage_inventory_items',
        'receive_stock',
        'issue_stock',
        'return_stock',
        'adjust_stock',
        'view_stock_ledger',
    ] as $permissionKey) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = :permission_key AND is_active = 1');
        $stmt->execute([':permission_key' => $permissionKey]);
        assertStore((int)$stmt->fetchColumn() === 1, 'Missing permission ' . $permissionKey . '.');
    }

    assertStore(in_array('inventory_items', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Inventory items table is missing.');
    assertStore(in_array('stock_transactions', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Stock transactions table is missing.');
    assertStore(in_array('department_stock_balances', $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), true), 'Department stock balance table is missing.');
    assertStore(str_contains(file_get_contents(__DIR__ . '/../layouts/sidebar.php'), '/modules/store/index.php'), 'Sidebar missing Store destination.');
    assertStore(!str_contains(file_get_contents(__DIR__ . '/../modules/visits/partials/workspace_navigation.php'), '/modules/store/index.php'), 'Encounter Workspace gained a Store tab unexpectedly.');

    assertStore((new PermissionService($pdo))->canViewInventory($store), 'Store officer should be able to view inventory.');
    assertStore((new PermissionService($pdo))->canManageInventoryItems($store), 'Store officer should manage inventory items.');
    assertStore((new PermissionService($pdo))->canReceiveStock($store), 'Store officer should receive stock.');
    assertStore((new PermissionService($pdo))->canIssueStock($store), 'Store officer should issue stock.');
    assertStore((new PermissionService($pdo))->canReturnStock($store), 'Store officer should return stock.');
    assertStore((new PermissionService($pdo))->canAdjustStock($store), 'Store officer should adjust stock.');
    assertStore((new PermissionService($pdo))->canViewStockLedger($store), 'Store officer should view ledger.');
    assertStore((new PermissionService($pdo))->canViewInventory($doctor), 'Doctor should be able to view inventory.');
    assertStore(!(new PermissionService($pdo))->canManageInventoryItems($doctor), 'Doctor should not manage inventory items.');

    $billableId = null;
    $pdo->prepare('
        INSERT INTO billable_items (
            item_code, item_name, item_type, department_id, description,
            unit_price, unit, is_active, created_by, created_at, updated_at
        ) VALUES (
            :item_code, :item_name, :item_type, :department_id, :description,
            :unit_price, :unit, 1, :created_by, NOW(), NOW()
        )
    ')->execute([
        ':item_code' => 'STB-001',
        ':item_name' => 'Test Stock Price',
        ':item_type' => 'Product',
        ':department_id' => null,
        ':description' => 'Linked catalogue item.',
        ':unit_price' => 25.00,
        ':unit' => 'Pack',
        ':created_by' => (int)$admin['id'],
    ]);
    $billableId = (int)$pdo->lastInsertId();

    $item = requireStoreSuccess($service->createItem([
        'item_code' => 'STO-ITEM-001',
        'item_name' => 'Test Inventory Item',
        'category' => 'Consumable',
        'unit' => 'Pack',
        'description' => 'Used by store tests.',
        'billable_item_id' => $billableId,
        'is_active' => 1,
    ], $store), 'Create inventory item');
    $itemId = (int)$item['inventory_item_id'];

    $duplicate = $service->createItem([
        'item_code' => 'STO-ITEM-001',
        'item_name' => 'Duplicate Item',
        'category' => 'Consumable',
        'unit' => 'Pack',
    ], $store);
    assertStore(($duplicate['success'] ?? true) === false, 'Duplicate inventory code was accepted.');

    $listed = $service->searchItems(['item_code' => 'STO-ITEM'], $store);
    assertStore(count($listed) >= 1, 'Inventory search returned no rows.');

    $viewItem = $service->getItemById($itemId, $doctor);
    assertStore($viewItem !== null && (string)$viewItem['item_code'] === 'STO-ITEM-001', 'Created inventory item could not be viewed.');
    assertStore((int)($viewItem['billable_item_id'] ?? 0) === $billableId, 'Billable item linkage was not preserved.');

    $updated = requireStoreSuccess($service->updateItem($itemId, [
        'item_code' => 'STO-ITEM-001',
        'item_name' => 'Test Inventory Item Updated',
        'category' => 'Consumable',
        'unit' => 'Pack',
        'description' => 'Updated description.',
        'billable_item_id' => $billableId,
        'is_active' => 1,
    ], $store), 'Update inventory item');
    assertStore(($updated['success'] ?? false) === true, 'Inventory item update failed.');

    $deactivated = requireStoreSuccess($service->deactivateItem($itemId, $admin), 'Deactivate inventory item');
    assertStore(($deactivated['success'] ?? false) === true, 'Deactivate inventory item failed.');

    $reactivated = requireStoreSuccess($service->activateItem($itemId, $store), 'Activate inventory item');
    assertStore(($reactivated['success'] ?? false) === true, 'Activate inventory item failed.');

    $receive = requireStoreSuccess($service->receiveStock([
        'inventory_item_id' => $itemId,
        'quantity' => 100,
        'reference' => 'DEL-001',
        'remarks' => 'Initial delivery.',
    ], $store), 'Receive stock');
    assertStore(($receive['success'] ?? false) === true, 'Receive stock failed.');

    $issuePharmacy = requireStoreSuccess($service->issueStock([
        'inventory_item_id' => $itemId,
        'department_id' => $pharmacyDepartmentId,
        'quantity' => 30,
        'reference' => 'ISS-001',
        'remarks' => 'Issued to Pharmacy.',
    ], $store), 'Issue stock to Pharmacy');

    $issueTheatre = requireStoreSuccess($service->issueStock([
        'inventory_item_id' => $itemId,
        'department_id' => $theatreDepartmentId,
        'quantity' => 20,
        'reference' => 'ISS-002',
        'remarks' => 'Issued to Theatre.',
    ], $store), 'Issue stock to Theatre');

    $returnStock = requireStoreSuccess($service->returnStock([
        'inventory_item_id' => $itemId,
        'department_id' => $pharmacyDepartmentId,
        'quantity' => 5,
        'reference' => 'RET-001',
        'remarks' => 'Unused packs returned.',
    ], $store), 'Return stock');

    $adjustDecrease = requireStoreSuccess($service->adjustStock([
        'inventory_item_id' => $itemId,
        'department_id' => $storeDepartmentId,
        'quantity' => 5,
        'adjustment_mode' => 'Decrease',
        'reference' => 'ADJ-001',
        'remarks' => 'Physical count correction.',
    ], $store), 'Decrease adjustment');

    $adjustIncrease = requireStoreSuccess($service->adjustStock([
        'inventory_item_id' => $itemId,
        'department_id' => $laboratoryDepartmentId,
        'quantity' => 4,
        'adjustment_mode' => 'Increase',
        'reference' => 'ADJ-002',
        'remarks' => 'Found during count.',
    ], $store), 'Increase adjustment');

    $storeBalance = $service->getDepartmentBalance($itemId, $storeDepartmentId, $admin);
    $pharmacyBalance = $service->getDepartmentBalance($itemId, $pharmacyDepartmentId, $admin);
    $theatreBalance = $service->getDepartmentBalance($itemId, $theatreDepartmentId, $admin);
    $labBalance = $service->getDepartmentBalance($itemId, $laboratoryDepartmentId, $admin);

    assertStore((float)($storeBalance['quantity'] ?? 0) === 50.00, 'Store balance is incorrect.');
    assertStore((float)($pharmacyBalance['quantity'] ?? 0) === 25.00, 'Pharmacy balance is incorrect.');
    assertStore((float)($theatreBalance['quantity'] ?? 0) === 20.00, 'Theatre balance is incorrect.');
    assertStore((float)($labBalance['quantity'] ?? 0) === 4.00, 'Laboratory balance is incorrect.');

    $departmentStock = $service->listDepartmentStock($storeDepartmentId, $admin);
    assertStore($departmentStock !== [], 'Department stock list is empty.');

    $ledger = $service->getItemLedger($itemId, $admin);
    assertStore(count($ledger) >= 5, 'Stock ledger is incomplete.');

    $insufficient = $service->issueStock([
        'inventory_item_id' => $itemId,
        'department_id' => $pharmacyDepartmentId,
        'quantity' => 999,
        'reference' => 'ISS-FAIL',
        'remarks' => 'Should fail.',
    ], $store);
    assertStore(($insufficient['success'] ?? true) === false, 'Insufficient stock issue was accepted.');

    $doctorDenied = $service->createItem([
        'item_code' => 'STO-DENIED-001',
        'item_name' => 'Doctor should not create',
        'category' => 'Consumable',
        'unit' => 'Pack',
    ], $doctor);
    assertStore(($doctorDenied['success'] ?? true) === false, 'Doctor created inventory item unexpectedly.');

    $nurseDenied = $service->issueStock([
        'inventory_item_id' => $itemId,
        'department_id' => $pharmacyDepartmentId,
        'quantity' => 1,
        'reference' => 'ISS-DENIED',
        'remarks' => 'Should fail.',
    ], $nurse);
    assertStore(($nurseDenied['success'] ?? true) === false, 'Nurse issued stock unexpectedly.');

    $auditCount = (int)$pdo->query("
        SELECT COUNT(*)
        FROM audit_logs
        WHERE module = 'Store'
          AND action IN (
              'INVENTORY_ITEM_CREATED',
              'INVENTORY_ITEM_UPDATED',
              'INVENTORY_ITEM_ACTIVATED',
              'INVENTORY_ITEM_DEACTIVATED',
              'STOCK_RECEIVED',
              'STOCK_ISSUED',
              'STOCK_RETURNED',
              'STOCK_ADJUSTED'
          )
    ")->fetchColumn();
    assertStore($auditCount >= 7, 'Store audit entries were not written.');

    assertStore(str_contains(file_get_contents(__DIR__ . '/../modules/store/_stock_form.php'), 'csrfField()'), 'Store stock forms are missing CSRF protection.');

    echo 'PASS: Phase 4.2 Store / Inventory regression passed.' . PHP_EOL;
} finally {
    $pdo->exec("DELETE FROM audit_logs WHERE module = 'Store' AND (action LIKE 'INVENTORY_ITEM_%' OR action LIKE 'STOCK_%')");
    $pdo->exec("DELETE FROM stock_transactions WHERE inventory_item_id IN (SELECT id FROM inventory_items WHERE item_code LIKE 'STO-%')");
    $pdo->exec("DELETE FROM department_stock_balances WHERE inventory_item_id IN (SELECT id FROM inventory_items WHERE item_code LIKE 'STO-%')");
    $pdo->exec("DELETE FROM inventory_items WHERE item_code LIKE 'STO-%'");
    $pdo->exec("DELETE FROM billable_items WHERE item_code LIKE 'STB-%'");
    $pdo->exec("DELETE FROM user_departments WHERE user_id IN (SELECT id FROM users WHERE username = 'dev_store')");
    $pdo->exec("DELETE FROM users WHERE username = 'dev_store'");
}
