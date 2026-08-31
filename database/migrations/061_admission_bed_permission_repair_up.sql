-- Ensure admission/bed assignment access matches the current workflow policy.
-- Nurse, Doctor, Receptionist, Records Officer, and System Administrator may admit
-- active encounters and transfer/change ward-bed assignment. Super Administrator
-- remains covered by the global permission grant.

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('System Administrator', 'Receptionist', 'Records Officer', 'Doctor', 'Nurse')
  AND p.permission_key IN ('view_admissions', 'create_admission', 'transfer_admission');

