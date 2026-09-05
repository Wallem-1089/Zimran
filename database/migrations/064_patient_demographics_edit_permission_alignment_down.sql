-- Revert patient demographic edit permission alignment.
-- Keeps the permission available; removes only the grants introduced by this migration.

DELETE rp FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key = 'edit_patient_demographics'
  AND r.role_name IN ('Super Administrator', 'Receptionist', 'Records Officer');
