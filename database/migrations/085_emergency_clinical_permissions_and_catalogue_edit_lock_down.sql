-- Revert Migration 085 emergency clinical grants and Pharmacy catalogue edit lock.

DELETE rp
FROM role_permissions rp
INNER JOIN roles r
    ON r.id = rp.role_id
INNER JOIN permissions p
    ON p.id = rp.permission_id
WHERE r.role_name IN ('Emergency Doctor', 'Emergency Nurse', 'Triage Nurse')
  AND p.permission_key IN (
      'view_vital_signs',
      'create_vital_signs',
      'edit_vital_signs',
      'view_nursing',
      'create_nursing',
      'edit_nursing',
      'complete_nursing',
      'view_laboratory',
      'create_laboratory_request',
      'view_radiology',
      'create_radiology_request',
      'view_ecg',
      'create_ecg_request',
      'view_pop',
      'create_pop_request',
      'view_physiotherapy',
      'create_physiotherapy',
      'view_theatre',
      'create_theatre',
      'edit_theatre',
      'complete_theatre',
      'view_pharmacy',
      'create_prescription',
      'edit_prescription'
  );

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
    ON p.permission_key = 'edit_billable_items'
LEFT JOIN role_permissions rp
    ON rp.role_id = r.id
   AND rp.permission_id = p.id
WHERE r.role_name IN ('Pharmacist', 'Pharmacy')
  AND rp.id IS NULL;
