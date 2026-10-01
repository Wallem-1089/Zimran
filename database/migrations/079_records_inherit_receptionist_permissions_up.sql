-- Allow Medical Records / Records Officer to do everything Receptionist can do.
-- This re-syncs role grants added after the earlier Reception/Records alignment.

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT records.id, p.id, NOW()
FROM roles records
INNER JOIN roles reception ON reception.role_name = 'Receptionist'
INNER JOIN role_permissions rp ON rp.role_id = reception.id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE records.role_name = 'Records Officer'
  AND p.permission_key <> 'delete_patient';

INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
SELECT records.id, p.id, NOW()
FROM roles records
INNER JOIN permissions p
WHERE records.role_name = 'Records Officer'
  AND p.permission_key = 'create_encounter';
