DELETE rp FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_consultation',
    'create_consultation',
    'edit_consultation',
    'complete_consultation'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_consultation',
    'create_consultation',
    'edit_consultation',
    'complete_consultation'
);

DROP TABLE IF EXISTS department_notifications;
DROP TABLE IF EXISTS consultations;
