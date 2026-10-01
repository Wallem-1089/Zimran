-- Restore the previous POP/Casting presentation.

ALTER TABLE visits
    MODIFY visit_status ENUM(
        'Waiting','Reception','Records','Nursing','Doctor','Laboratory','X-Ray',
        'ECG','Plaster','POP','Pharmacy','Physiotherapy','Theatre','Accounts','Store',
        'Completed','Cancelled'
    ) NOT NULL DEFAULT 'Waiting';

UPDATE visits
SET visit_status = 'POP'
WHERE visit_status = 'Plaster';

UPDATE departments
SET department_name = 'POP',
    department_code = 'POP',
    description = 'Plaster of Paris and casting services'
WHERE department_name = 'Plaster'
  AND NOT EXISTS (
      SELECT 1 FROM (
          SELECT id FROM departments WHERE department_name = 'POP'
      ) existing_pop_department
  );

UPDATE roles
SET role_name = 'POP Technician',
    description = 'POP/casting personnel for request processing and procedure documentation'
WHERE role_name = 'Plaster Technician'
  AND NOT EXISTS (
      SELECT 1 FROM (
          SELECT id FROM roles WHERE role_name = 'POP Technician'
      ) existing_pop_role
  );

UPDATE permissions
SET permission_name = 'View POP',
    module = 'POP',
    description = 'View POP requests and casting/procedure records.'
WHERE permission_key = 'view_pop';

UPDATE permissions
SET permission_name = 'Create POP Request',
    module = 'POP',
    description = 'Create a clinical or direct POP/casting request.'
WHERE permission_key = 'create_pop_request';

UPDATE permissions
SET permission_name = 'Process POP Request',
    module = 'POP',
    description = 'Start and process POP/casting requests.'
WHERE permission_key = 'process_pop_request';

UPDATE permissions
SET permission_name = 'Record POP Procedure',
    module = 'POP',
    description = 'Document POP/casting procedure details.'
WHERE permission_key = 'record_pop_procedure';

UPDATE permissions
SET permission_name = 'Edit POP Record',
    module = 'POP',
    description = 'Edit POP/casting records before completion.'
WHERE permission_key = 'edit_pop_record';

UPDATE permissions
SET permission_name = 'Complete POP Request',
    module = 'POP',
    description = 'Complete POP/casting requests after procedure documentation.'
WHERE permission_key = 'complete_pop_request';

UPDATE form_definitions
SET form_name = 'POP Procedure Record',
    description = 'Optional extra POP/casting procedure fields.'
WHERE form_key = 'pop_record';
