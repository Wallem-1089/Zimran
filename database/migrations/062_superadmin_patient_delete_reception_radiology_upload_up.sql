-- Superadmin patient delete, safer superadmin assignment support, receptionist/records alignment,
-- generated-staff first-login password change, and X-Ray/Radiology scanned document upload.

ALTER TABLE radiology_reports
    ADD COLUMN IF NOT EXISTS chart_original_name VARCHAR(255) NULL AFTER recommendation,
    ADD COLUMN IF NOT EXISTS chart_stored_path VARCHAR(500) NULL AFTER chart_original_name,
    ADD COLUMN IF NOT EXISTS chart_mime_type VARCHAR(120) NULL AFTER chart_stored_path,
    ADD COLUMN IF NOT EXISTS chart_file_size BIGINT NULL AFTER chart_mime_type;

DELETE rp FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key = 'delete_patient'
  AND r.role_name <> 'Super Administrator';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Super Administrator'
  AND p.permission_key = 'delete_patient';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT reception.id, p.id
FROM roles reception
INNER JOIN roles records ON records.role_name = 'Records Officer'
INNER JOIN role_permissions rp ON rp.role_id = records.id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE reception.role_name = 'Receptionist'
  AND p.permission_key <> 'delete_patient';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT records.id, p.id
FROM roles records
INNER JOIN roles reception ON reception.role_name = 'Receptionist'
INNER JOIN role_permissions rp ON rp.role_id = reception.id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE records.role_name = 'Records Officer'
  AND p.permission_key <> 'delete_patient';

UPDATE users
SET must_change_password = 1,
    updated_at = NOW()
WHERE username IN (
    'mmobash',
    'eessien',
    'otheophilus',
    'iblessing',
    'schukwuwike',
    'iirene',
    'nblessing',
    'eflorence',
    'aosariemen',
    'rbridget',
    'omercy',
    'osarah',
    'oogbale',
    'ujuliet',
    'eaugustine',
    'ichristiana',
    'ofaith',
    'iosebumere',
    'ofavour',
    'merometse',
    'mdestiny',
    'ofaith2',
    'oomon',
    'ojoy',
    'ntestimony',
    'echidinma',
    'ostella',
    'ochristabel',
    'cdaniel',
    'esylvester',
    'oahmadu',
    'ojuliet',
    'ebright',
    'oosasumwen',
    'ubenedita',
    'aagnes',
    'neyo',
    'ograce',
    'uolushola',
    'odoris',
    'oesohe',
    'eeunice',
    'ijudith',
    'oblessing',
    'edalington',
    'mmary',
    'oracheal',
    'ielizabeth',
    'ochristiana',
    'esolomon',
    'oblessing2',
    'oloveth',
    'sjoy',
    'ajohn',
    'gwisdom',
    'ifestus',
    'ubright',
    'rtoliah',
    'ofestusiyen'
);
