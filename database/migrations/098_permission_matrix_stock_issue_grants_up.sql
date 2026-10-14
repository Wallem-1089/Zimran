-- Ensure Pharmacy stock issuing remains matrix-controlled.
-- The PermissionService stock helpers now rely on role/user permissions instead
-- of hardcoded department checks, so Pharmacist needs the explicit issue_stock
-- grant for drug stock issuing to other departments.

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_key = 'issue_stock'
WHERE r.role_name = 'Pharmacist'
  AND p.is_active = 1
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );
