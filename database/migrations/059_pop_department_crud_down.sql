-- Rollback POP request/procedure workflow.

DELETE rp FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_pop','create_pop_request','process_pop_request',
    'record_pop_procedure','edit_pop_record','complete_pop_request'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_pop','create_pop_request','process_pop_request',
    'record_pop_procedure','edit_pop_record','complete_pop_request'
);

DROP TABLE IF EXISTS pop_records;
DROP TABLE IF EXISTS pop_requests;

DELETE FROM roles WHERE role_name = 'POP Technician';
DELETE FROM departments WHERE department_name = 'POP';

ALTER TABLE visits
    MODIFY visit_status ENUM(
        'Waiting','Reception','Records','Nursing','Doctor','Laboratory','X-Ray',
        'ECG','Pharmacy','Physiotherapy','Theatre','Accounts','Store',
        'Completed','Cancelled'
    ) NOT NULL DEFAULT 'Waiting';
