SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_billable_items',
    'create_billable_items',
    'edit_billable_items',
    'manage_billable_item_status'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_billable_items',
    'create_billable_items',
    'edit_billable_items',
    'manage_billable_item_status'
);

DROP TABLE IF EXISTS billable_items;

SET FOREIGN_KEY_CHECKS = 1;
