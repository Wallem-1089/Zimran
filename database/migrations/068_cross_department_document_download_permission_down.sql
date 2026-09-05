DELETE up
FROM user_permissions up
INNER JOIN permissions p
    ON p.id = up.permission_id
WHERE p.permission_key = 'download_cross_department_medical_documents';

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p
    ON p.id = rp.permission_id
WHERE p.permission_key = 'download_cross_department_medical_documents';

DELETE FROM permissions
WHERE permission_key = 'download_cross_department_medical_documents';
