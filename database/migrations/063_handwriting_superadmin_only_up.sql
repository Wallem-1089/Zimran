-- Restrict handwriting entry mode to Super Administrator by default.
-- User-level permission overrides can still grant it to selected accounts later.

UPDATE permissions
SET permission_name = 'Use Handwriting Entry Mode',
    module = 'Clinical Entry',
    description = 'Use the handwriting/touch-pad entry mode on supported narrative forms.',
    is_active = 1
WHERE permission_key = 'use_consultation_handwriting';

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
INNER JOIN roles r ON r.id = rp.role_id
WHERE p.permission_key = 'use_consultation_handwriting'
  AND r.role_name <> 'Super Administrator';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p ON p.permission_key = 'use_consultation_handwriting'
WHERE r.role_name = 'Super Administrator'
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );
