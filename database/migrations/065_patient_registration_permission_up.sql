-- Add explicit patient registration permission.
-- Default allow: Super Administrator, System Administrator, Receptionist, Records Officer.

INSERT INTO permissions (
    permission_key,
    permission_name,
    module,
    description,
    is_active
)
SELECT
    'register_patient',
    'Register Patient',
    'Patients',
    'Register new patient demographic records.',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE permission_key = 'register_patient'
);

DELETE rp FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key = 'register_patient'
  AND r.role_name NOT IN ('Super Administrator', 'System Administrator', 'Receptionist', 'Records Officer');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Super Administrator', 'System Administrator', 'Receptionist', 'Records Officer')
  AND p.permission_key = 'register_patient';
