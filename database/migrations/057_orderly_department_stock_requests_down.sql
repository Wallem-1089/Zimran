-- Remove default Orderly stock-request grants.
-- The role/department rows are deactivated instead of deleted to preserve any user/history links.

DELETE rp
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE r.role_name = 'Orderly'
  AND p.permission_key IN (
      'view_stock_requests',
      'create_stock_request'
  );

UPDATE roles
SET is_active = 0
WHERE role_name = 'Orderly';

UPDATE departments
SET is_active = 0
WHERE department_name = 'Orderly';
