-- Put Family Card administration under the permission matrix.

INSERT INTO permissions (
    permission_key, permission_name, module, description, is_active
)
SELECT
    'manage_family_cards',
    'Manage Family Cards',
    'Patients',
    'View and manage Family Card plans and members.',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE permission_key = 'manage_family_cards'
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_key = 'manage_family_cards'
WHERE r.role_name IN ('System Administrator', 'Records Officer', 'Receptionist')
  AND p.is_active = 1
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );
