INSERT INTO role_permissions (role_id, permission_id, assigned_by)
SELECT r.id, p.id, NULL
FROM roles r
INNER JOIN permissions p ON p.permission_key IN ('review_stock_request', 'issue_stock_request')
WHERE r.role_name = 'Pharmacist'
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );
