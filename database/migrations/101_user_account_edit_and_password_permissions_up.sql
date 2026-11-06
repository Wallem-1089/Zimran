-- Allow the permission matrix to independently remove account editing and password reset
-- from System Administrators. Super Administrators retain their existing override.

INSERT INTO permissions (
    permission_key, permission_name, module, description, is_active
)
SELECT
    'edit_user_accounts',
    'Edit User Accounts',
    'Administration',
    'Edit user account and staff profile information.',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE permission_key = 'edit_user_accounts'
);

INSERT INTO permissions (
    permission_key, permission_name, module, description, is_active
)
SELECT
    'reset_user_passwords',
    'Reset User Passwords',
    'Administration',
    'Reset passwords for user accounts.',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE permission_key = 'reset_user_passwords'
);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON p.permission_key IN ('edit_user_accounts', 'reset_user_passwords')
WHERE r.role_name = 'System Administrator'
  AND p.is_active = 1
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );
