/*
|--------------------------------------------------------------------------
| Phase 1 - Milestone 1.1 User Management Rollback
|--------------------------------------------------------------------------
*/

ALTER TABLE users

    DROP FOREIGN KEY fk_users_locked_by,

    DROP INDEX idx_users_locked_at,

    DROP COLUMN locked_at,

    DROP COLUMN locked_by,

    DROP COLUMN lock_reason;
