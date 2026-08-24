SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_external_sales',
    'create_external_sale',
    'cancel_external_sale',
    'view_external_sale_receipts'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_external_sales',
    'create_external_sale',
    'cancel_external_sale',
    'view_external_sale_receipts'
);

DROP TABLE IF EXISTS external_sale_items;
DROP TABLE IF EXISTS external_sales;

SET FOREIGN_KEY_CHECKS = 1;
