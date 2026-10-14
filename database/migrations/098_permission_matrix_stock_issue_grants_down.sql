-- Revert the Pharmacy issue_stock grant added by migration 098.

DELETE rp
FROM role_permissions rp
JOIN roles r ON r.id = rp.role_id
JOIN permissions p ON p.id = rp.permission_id
WHERE r.role_name = 'Pharmacist'
  AND p.permission_key = 'issue_stock';
