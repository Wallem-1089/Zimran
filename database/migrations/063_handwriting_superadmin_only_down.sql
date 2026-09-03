-- Restore the earlier default Consultation handwriting grants.
-- User-level permission overrides are intentionally left untouched.

UPDATE permissions
SET permission_name = 'Use Consultation Handwriting',
    module = 'Consultation',
    description = 'Use the handwriting/touch-pad entry mode on Consultation forms.',
    is_active = 1
WHERE permission_key = 'use_consultation_handwriting';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p ON p.permission_key = 'use_consultation_handwriting'
WHERE r.role_name IN ('System Administrator', 'Doctor')
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );
