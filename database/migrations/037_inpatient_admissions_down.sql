SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE rp FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_admissions',
    'create_admission',
    'transfer_admission',
    'discharge_admission',
    'manage_wards_beds'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_admissions',
    'create_admission',
    'transfer_admission',
    'discharge_admission',
    'manage_wards_beds'
);

DROP TABLE IF EXISTS admission_movements;
DROP TABLE IF EXISTS admissions;
DROP TABLE IF EXISTS ward_beds;
DROP TABLE IF EXISTS wards;

SET FOREIGN_KEY_CHECKS = 1;
