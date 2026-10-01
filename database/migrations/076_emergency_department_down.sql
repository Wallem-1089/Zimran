-- Remove Emergency seed data where it is not referenced.

DELETE rp
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
WHERE r.role_name IN ('Emergency Doctor', 'Emergency Nurse', 'Triage Nurse');

DELETE FROM roles
WHERE role_name IN ('Emergency Doctor', 'Emergency Nurse', 'Triage Nurse');

DELETE FROM departments
WHERE department_name = 'Emergency'
  AND NOT EXISTS (
      SELECT 1 FROM visits WHERE current_department_id = departments.id
  )
  AND NOT EXISTS (
      SELECT 1 FROM users WHERE department_id = departments.id
  )
  AND NOT EXISTS (
      SELECT 1 FROM user_departments WHERE department_id = departments.id
  );

UPDATE visits
SET visit_status = 'Doctor'
WHERE visit_status = 'Emergency';

ALTER TABLE visits
    MODIFY visit_status ENUM(
        'Waiting','Reception','Records','Nursing','Doctor','Laboratory','X-Ray',
        'ECG','Plaster','POP','Pharmacy','Physiotherapy','Theatre','Accounts','Store',
        'Completed','Cancelled'
    ) NOT NULL DEFAULT 'Waiting';
