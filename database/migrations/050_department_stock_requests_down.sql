SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS stock_request_items;
DROP TABLE IF EXISTS stock_requests;

DELETE rp FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_stock_requests',
    'create_stock_request',
    'review_stock_request',
    'issue_stock_request',
    'cancel_stock_request'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_stock_requests',
    'create_stock_request',
    'review_stock_request',
    'issue_stock_request',
    'cancel_stock_request'
);

SET FOREIGN_KEY_CHECKS = 1;
