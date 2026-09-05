-- Remove explicit patient registration permission grants and permission row.

DELETE rp FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key = 'register_patient';

DELETE FROM user_permissions
WHERE permission_id IN (
    SELECT id FROM permissions WHERE permission_key = 'register_patient'
);

DELETE FROM permissions
WHERE permission_key = 'register_patient';
