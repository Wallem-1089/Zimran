-- Align patient demographic edit permission with current policy.
-- Default allow: Super Administrator, Receptionist, Records Officer.
-- Doctor access must be granted per-account through user permission overrides.

DELETE rp FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key = 'edit_patient_demographics'
  AND r.role_name NOT IN ('Super Administrator', 'Receptionist', 'Records Officer');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Super Administrator', 'Receptionist', 'Records Officer')
  AND p.permission_key = 'edit_patient_demographics';
