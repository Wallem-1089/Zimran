SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_inventory',
    'manage_inventory_items',
    'receive_stock',
    'issue_stock',
    'return_stock',
    'adjust_stock',
    'view_stock_ledger'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_inventory',
    'manage_inventory_items',
    'receive_stock',
    'issue_stock',
    'return_stock',
    'adjust_stock',
    'view_stock_ledger'
);

DROP TABLE IF EXISTS department_stock_balances;
DROP TABLE IF EXISTS stock_transactions;
DROP TABLE IF EXISTS inventory_items;

SET FOREIGN_KEY_CHECKS = 1;
