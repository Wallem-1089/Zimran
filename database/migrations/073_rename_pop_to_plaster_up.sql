-- Rename POP/Casting department presentation to Plaster.
-- Internal pop_* tables and permission keys are intentionally preserved.

ALTER TABLE visits
    MODIFY visit_status ENUM(
        'Waiting','Reception','Records','Nursing','Doctor','Laboratory','X-Ray',
        'ECG','Plaster','POP','Pharmacy','Physiotherapy','Theatre','Accounts','Store',
        'Completed','Cancelled'
    ) NOT NULL DEFAULT 'Waiting';

UPDATE visits
SET visit_status = 'Plaster'
WHERE visit_status = 'POP';

UPDATE departments
SET department_name = 'Plaster',
    department_code = 'PLASTER',
    description = 'Plaster services'
WHERE department_name = 'POP'
  AND NOT EXISTS (
      SELECT 1 FROM (
          SELECT id FROM departments WHERE department_name = 'Plaster'
      ) existing_plaster_department
  );

UPDATE roles
SET role_name = 'Plaster Technician',
    description = 'Plaster personnel for request processing and procedure documentation'
WHERE role_name = 'POP Technician'
  AND NOT EXISTS (
      SELECT 1 FROM (
          SELECT id FROM roles WHERE role_name = 'Plaster Technician'
      ) existing_plaster_role
  );

UPDATE permissions
SET permission_name = 'View Plaster',
    module = 'Plaster',
    description = 'View Plaster requests and procedure records.'
WHERE permission_key = 'view_pop';

UPDATE permissions
SET permission_name = 'Create Plaster Request',
    module = 'Plaster',
    description = 'Create a clinical Plaster request.'
WHERE permission_key = 'create_pop_request';

UPDATE permissions
SET permission_name = 'Process Plaster Request',
    module = 'Plaster',
    description = 'Start and process Plaster requests.'
WHERE permission_key = 'process_pop_request';

UPDATE permissions
SET permission_name = 'Record Plaster Procedure',
    module = 'Plaster',
    description = 'Document Plaster procedure details.'
WHERE permission_key = 'record_pop_procedure';

UPDATE permissions
SET permission_name = 'Edit Plaster Record',
    module = 'Plaster',
    description = 'Edit Plaster records before completion.'
WHERE permission_key = 'edit_pop_record';

UPDATE permissions
SET permission_name = 'Complete Plaster Request',
    module = 'Plaster',
    description = 'Complete Plaster requests after procedure documentation.'
WHERE permission_key = 'complete_pop_request';

UPDATE form_definitions
SET form_name = 'Plaster Procedure Record',
    description = 'Optional extra Plaster procedure fields.'
WHERE form_key = 'pop_record';
