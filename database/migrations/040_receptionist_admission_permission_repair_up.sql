-- Ensure Receptionist can admit patients.
-- Migration 037 defines this as intended access; this repair is idempotent.

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Receptionist'
  AND p.permission_key IN ('view_admissions', 'create_admission');

