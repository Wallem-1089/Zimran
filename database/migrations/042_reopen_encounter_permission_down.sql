-- Remove default role grants and permission introduced for encounter reopen.

DELETE rp
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE r.role_name IN ('Records Officer', 'Doctor')
  AND p.permission_key = 'reopen_encounter';

DELETE FROM permissions
WHERE permission_key = 'reopen_encounter';
