-- Remove user-level permission overrides and Consultation handwriting permission.

DELETE up
FROM user_permissions up
INNER JOIN permissions p ON p.id = up.permission_id
WHERE p.permission_key = 'use_consultation_handwriting';

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key = 'use_consultation_handwriting';

DELETE FROM permissions
WHERE permission_key = 'use_consultation_handwriting';

DROP TABLE IF EXISTS user_permissions;
