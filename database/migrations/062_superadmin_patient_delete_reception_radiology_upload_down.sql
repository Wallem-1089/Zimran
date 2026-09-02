-- Down migration intentionally keeps first-login password-change flags and role alignment.
-- It only removes the Radiology upload metadata columns and restores prior delete grants.

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Doctor', 'Records Officer')
  AND p.permission_key = 'delete_patient';

ALTER TABLE radiology_reports
    DROP COLUMN IF EXISTS chart_file_size,
    DROP COLUMN IF EXISTS chart_mime_type,
    DROP COLUMN IF EXISTS chart_stored_path,
    DROP COLUMN IF EXISTS chart_original_name;
