/* Phase 3.4: Laboratory CRUD rollback. */

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_laboratory',
    'create_laboratory_request',
    'process_laboratory_request',
    'enter_laboratory_result',
    'edit_laboratory_result',
    'complete_laboratory_request'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_laboratory',
    'create_laboratory_request',
    'process_laboratory_request',
    'enter_laboratory_result',
    'edit_laboratory_result',
    'complete_laboratory_request'
);

DROP TABLE IF EXISTS laboratory_results;
DROP TABLE IF EXISTS laboratory_requests;
