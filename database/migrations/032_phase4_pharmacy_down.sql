SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_pharmacy',
    'create_prescription',
    'edit_prescription',
    'dispense_prescription'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_pharmacy',
    'create_prescription',
    'edit_prescription',
    'dispense_prescription'
);

DROP TABLE IF EXISTS pharmacy_dispensing;
DROP TABLE IF EXISTS prescriptions;

SET FOREIGN_KEY_CHECKS = 1;
