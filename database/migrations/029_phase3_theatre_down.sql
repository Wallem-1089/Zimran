SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN ('view_theatre', 'create_theatre', 'edit_theatre', 'complete_theatre');

DELETE FROM permissions
WHERE permission_key IN ('view_theatre', 'create_theatre', 'edit_theatre', 'complete_theatre');

DROP TABLE IF EXISTS theatre_records;

SET FOREIGN_KEY_CHECKS = 1;
