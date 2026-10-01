-- Add explicit Emergency module permissions for sidebar, worklist, and reports.

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_emergency', 'View Emergency', 'Emergency', 'Access Emergency department options.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_emergency');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_emergency_worklist', 'View Emergency Worklist', 'Emergency', 'Access the Emergency department worklist from the sidebar.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_emergency_worklist');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_emergency_reports', 'View Emergency Reports', 'Reports', 'Access Emergency reports and the emergency register.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_emergency_reports');

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.permission_key IN (
    'view_emergency',
    'view_emergency_worklist',
    'view_emergency_reports'
)
WHERE r.role_name IN ('Super Administrator', 'System Administrator');
