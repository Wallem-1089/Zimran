/* Phase 3.5: Radiology CRUD rollback. */

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_radiology',
    'create_radiology_request',
    'process_radiology_request',
    'enter_radiology_report',
    'edit_radiology_report',
    'complete_radiology_request'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_radiology',
    'create_radiology_request',
    'process_radiology_request',
    'enter_radiology_report',
    'edit_radiology_report',
    'complete_radiology_request'
);

DROP TABLE IF EXISTS radiology_reports;
DROP TABLE IF EXISTS radiology_requests;
