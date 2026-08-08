/*
|--------------------------------------------------------------------------
| Rollback Phase 1 - Milestone 1.6 Enterprise System Settings
|--------------------------------------------------------------------------
*/

DELETE FROM role_permissions
WHERE permission_id IN (
    SELECT id FROM permissions WHERE permission_key = 'manage_settings'
);

DELETE FROM permissions
WHERE permission_key = 'manage_settings';

DROP TABLE system_setting_history;

DROP TABLE system_settings;
