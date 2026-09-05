DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key = 'view_patient_communications';

DELETE FROM permissions
WHERE permission_key = 'view_patient_communications';

DROP TABLE IF EXISTS patient_communications;

ALTER TABLE patients
    DROP COLUMN IF EXISTS whatsapp_number;
