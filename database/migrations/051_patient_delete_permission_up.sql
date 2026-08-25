-- Patient deletion permission.
-- This is intentionally permission-only; patient/encounter ID generators are not changed.

INSERT INTO permissions (
    permission_key,
    permission_name,
    module,
    description,
    is_active
)
VALUES (
    'delete_patient',
    'Delete Patient',
    'Patients',
    'Delete mistaken empty patient registrations. Existing linked records block deletion.',
    1
)
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Records Officer', 'Doctor')
  AND p.permission_key = 'delete_patient';
