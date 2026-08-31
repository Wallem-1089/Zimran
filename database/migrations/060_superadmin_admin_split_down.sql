-- Roll back Super Administrator split.

UPDATE users u
INNER JOIN roles r ON r.role_name = 'System Administrator'
INNER JOIN departments d ON d.department_name = 'Administrator'
SET u.role_id = r.id,
    u.department_id = d.id,
    u.updated_at = NOW()
WHERE u.username = 'walter'
   OR (u.first_name = 'Walter' AND u.last_name = 'Ikhile');

DELETE rp FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
WHERE r.role_name = 'Super Administrator';

DELETE FROM roles WHERE role_name = 'Super Administrator';
DELETE FROM departments WHERE department_name = 'Super Administrator';

UPDATE roles
SET description = 'Full system access',
    updated_at = NOW()
WHERE role_name = 'System Administrator';
