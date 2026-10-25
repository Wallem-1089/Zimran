DELETE rp
FROM role_permissions rp
JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key = 'manage_family_cards';

DELETE FROM permissions
WHERE permission_key = 'manage_family_cards';
