INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT
    'download_cross_department_medical_documents',
    'Download Cross-Department Medical Documents',
    'Medical Records',
    'Allow downloading medical document files uploaded by departments other than the user active department.',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM permissions
    WHERE permission_key = 'download_cross_department_medical_documents'
);

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p
    ON p.permission_key = 'download_cross_department_medical_documents'
WHERE r.role_name = 'Super Administrator'
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );
