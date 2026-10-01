-- Add Emergency as a true encounter-ownership department.

ALTER TABLE visits
    MODIFY visit_status ENUM(
        'Waiting','Reception','Records','Nursing','Doctor','Emergency','Laboratory','X-Ray',
        'ECG','Plaster','POP','Pharmacy','Physiotherapy','Theatre','Accounts','Store',
        'Completed','Cancelled'
    ) NOT NULL DEFAULT 'Waiting';

INSERT INTO departments (
    department_name,
    department_code,
    description,
    department_type,
    queue_enabled,
    is_active,
    display_order
)
SELECT 'Emergency', 'EMERGENCY', 'Emergency care and triage', 'Clinical', 1, 1, 6
WHERE NOT EXISTS (
    SELECT 1 FROM departments WHERE department_name = 'Emergency'
);

UPDATE departments
SET department_code = COALESCE(NULLIF(department_code, ''), 'EMERGENCY'),
    description = COALESCE(description, 'Emergency care and triage'),
    department_type = 'Clinical',
    queue_enabled = 1,
    is_active = 1
WHERE department_name = 'Emergency';

INSERT INTO roles (role_name, description, is_active)
SELECT 'Emergency Doctor', 'Emergency department doctor', 1
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'Emergency Doctor');

INSERT INTO roles (role_name, description, is_active)
SELECT 'Emergency Nurse', 'Emergency triage and emergency care nurse', 1
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'Emergency Nurse');

INSERT INTO roles (role_name, description, is_active)
SELECT 'Triage Nurse', 'Emergency triage nurse', 1
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'Triage Nurse');

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p ON p.permission_key IN (
    'view_encounter',
    'create_encounter',
    'transfer_encounter',
    'receive_encounter',
    'change_encounter_status',
    'edit_encounter',
    'view_medical_record',
    'view_clinical_safety',
    'view_clinical_notes',
    'create_encounter_notes',
    'view_vital_signs',
    'create_vital_signs',
    'edit_vital_signs',
    'view_nursing',
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
    'view_pharmacy',
    'create_prescription',
    'create_billing_request'
)
WHERE r.role_name IN ('Emergency Doctor', 'Emergency Nurse', 'Triage Nurse')
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );
