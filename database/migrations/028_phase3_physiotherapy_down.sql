/* Phase 3.6: Physiotherapy CRUD rollback. */

DELETE FROM role_permissions
WHERE permission_id IN (
    SELECT id FROM permissions
    WHERE permission_key IN (
        'view_encounter',
        'view_physiotherapy',
        'create_physiotherapy',
        'edit_physiotherapy',
        'manage_physiotherapy_sessions',
        'complete_physiotherapy'
    )
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_physiotherapy',
    'create_physiotherapy',
    'edit_physiotherapy',
    'manage_physiotherapy_sessions',
    'complete_physiotherapy'
);

DROP TABLE IF EXISTS physiotherapy_sessions;
DROP TABLE IF EXISTS physiotherapy_records;
