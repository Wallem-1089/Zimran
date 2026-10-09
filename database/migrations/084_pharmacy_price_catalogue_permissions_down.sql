-- Revert price catalogue permissions to the original Accounts-owned Migration 030 shape.

DELETE rp
FROM role_permissions rp
INNER JOIN roles r
    ON r.id = rp.role_id
INNER JOIN permissions p
    ON p.id = rp.permission_id
WHERE r.role_name IN ('Pharmacist', 'Pharmacy')
  AND p.permission_key IN (
      'create_billable_items',
      'edit_billable_items',
      'manage_billable_item_status'
  );

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
    ON p.permission_key IN (
        'view_billable_items',
        'create_billable_items',
        'edit_billable_items',
        'manage_billable_item_status'
    )
LEFT JOIN role_permissions rp
    ON rp.role_id = r.id
   AND rp.permission_id = p.id
WHERE r.role_name IN ('Accounts', 'Accountant')
  AND rp.id IS NULL;
