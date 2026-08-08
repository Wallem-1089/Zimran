/*
|--------------------------------------------------------------------------
| Phase 1 - Milestone 1.2 Roles and Permissions Rollback
|--------------------------------------------------------------------------
*/

DROP TABLE role_permissions;

DROP TABLE permissions;

ALTER TABLE roles

    DROP INDEX idx_roles_active,

    DROP COLUMN is_active;
