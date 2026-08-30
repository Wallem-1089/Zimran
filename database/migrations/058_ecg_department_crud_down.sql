DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_ecg','create_ecg_request','process_ecg_request',
    'upload_ecg_chart','edit_ecg_report','complete_ecg_request'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_ecg','create_ecg_request','process_ecg_request',
    'upload_ecg_chart','edit_ecg_report','complete_ecg_request'
);

DROP TABLE IF EXISTS ecg_reports;
DROP TABLE IF EXISTS ecg_requests;

UPDATE roles SET is_active = 0 WHERE role_name = 'ECG Technician';
UPDATE departments SET is_active = 0 WHERE department_name = 'ECG';

ALTER TABLE visits
    MODIFY visit_status ENUM(
        'Waiting','Reception','Records','Nursing','Doctor','Laboratory','X-Ray',
        'Pharmacy','Physiotherapy','Theatre','Accounts','Store',
        'Completed','Cancelled'
    ) NOT NULL DEFAULT 'Waiting';
