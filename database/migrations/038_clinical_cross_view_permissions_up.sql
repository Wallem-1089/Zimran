SET NAMES utf8mb4;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN (
    'Doctor',
    'Nurse',
    'Laboratory Scientist',
    'Radiographer',
    'Physiotherapist',
    'Theatre Staff',
    'Pharmacist',
    'Records Officer'
)
  AND p.permission_key IN (
      'view_medical_record',
      'view_consultation',
      'view_vital_signs',
      'view_nursing',
      'view_laboratory',
      'view_radiology',
      'view_physiotherapy',
      'view_theatre',
      'view_pharmacy'
  );

