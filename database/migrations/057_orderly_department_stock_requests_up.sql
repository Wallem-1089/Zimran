-- Orderly department and role.
-- Orderly users can only use the Stock Requests feature by default.

INSERT INTO departments (
    department_name,
    department_code,
    description,
    department_type,
    queue_enabled,
    is_active,
    display_order
)
SELECT
    'Orderly',
    'ORD',
    'Orderly support services',
    'Support',
    0,
    1,
    120
WHERE NOT EXISTS (
    SELECT 1 FROM departments WHERE department_name = 'Orderly'
);

INSERT INTO roles (
    role_name,
    description,
    is_active
)
SELECT
    'Orderly',
    'Orderly support staff with stock request access',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE role_name = 'Orderly'
);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Orderly'
  AND p.permission_key IN (
      'view_stock_requests',
      'create_stock_request'
  );
