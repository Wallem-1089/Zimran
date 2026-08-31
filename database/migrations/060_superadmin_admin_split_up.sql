-- Split full Super Administrator from ordinary System Administrator.

INSERT INTO departments (department_name, department_code, description, department_type, queue_enabled, is_active, display_order)
SELECT 'Super Administrator', 'SUPERADMIN', 'Full system super administration', 'Administrative', 0, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name = 'Super Administrator');

INSERT INTO roles (role_name, description, is_active)
SELECT 'Super Administrator', 'Full unrestricted system access', 1
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'Super Administrator');

UPDATE roles
SET description = 'Administration functionality and cross-department worklist visibility',
    updated_at = NOW()
WHERE role_name = 'System Administrator';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Super Administrator';

DELETE rp FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
WHERE r.role_name = 'System Administrator';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'System Administrator'
  AND p.permission_key IN (
      'view_encounter',
      'manage_users',
      'manage_roles',
      'manage_permissions',
      'manage_settings'
  );

UPDATE users u
INNER JOIN roles r ON r.role_name = 'Super Administrator'
INNER JOIN departments d ON d.department_name = 'Super Administrator'
SET u.role_id = r.id,
    u.department_id = d.id,
    u.status = 'Active',
    u.locked_at = NULL,
    u.locked_by = NULL,
    u.lock_reason = NULL,
    u.failed_login_attempts = 0,
    u.updated_at = NOW()
WHERE u.username = 'walter'
   OR (u.first_name = 'Walter' AND u.last_name = 'Ikhile');

INSERT INTO user_departments (user_id, department_id, is_primary, is_active, assigned_by)
SELECT u.id, d.id, 1, 1, 1
FROM users u
INNER JOIN departments d ON d.department_name = 'Super Administrator'
WHERE u.username = 'walter'
   OR (u.first_name = 'Walter' AND u.last_name = 'Ikhile')
ON DUPLICATE KEY UPDATE
    is_primary = 1,
    is_active = 1;

UPDATE user_departments ud
INNER JOIN users u ON u.id = ud.user_id
INNER JOIN departments d ON d.id = ud.department_id
SET ud.is_primary = CASE WHEN d.department_name = 'Super Administrator' THEN 1 ELSE 0 END
WHERE u.username = 'walter'
   OR (u.first_name = 'Walter' AND u.last_name = 'Ikhile');
