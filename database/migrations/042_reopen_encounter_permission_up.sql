-- Phase: Encounter lifecycle permission repair
-- Adds tightly controlled permission for reopening completed encounters.

INSERT INTO permissions (
    permission_key,
    permission_name,
    module,
    description,
    is_active
)
VALUES (
    'reopen_encounter',
    'Reopen Encounter',
    'Visits',
    'Reopen a completed encounter with a required reason.',
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
  AND p.permission_key = 'reopen_encounter';
