-- Remove explicit Emergency module permission seeds.

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_emergency',
    'view_emergency_worklist',
    'view_emergency_reports'
);

DELETE up
FROM user_permissions up
INNER JOIN permissions p ON p.id = up.permission_id
WHERE p.permission_key IN (
    'view_emergency',
    'view_emergency_worklist',
    'view_emergency_reports'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_emergency',
    'view_emergency_worklist',
    'view_emergency_reports'
);
