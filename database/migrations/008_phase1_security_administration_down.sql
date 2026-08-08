/*
|--------------------------------------------------------------------------
| Phase 1 - Milestone 1.4 Security Administration Rollback
|--------------------------------------------------------------------------
*/

DROP TABLE password_history;

DROP TABLE active_sessions;

ALTER TABLE audit_logs

    DROP FOREIGN KEY fk_audit_department,

    DROP INDEX idx_audit_action_created,

    DROP INDEX idx_audit_user_created,

    DROP INDEX idx_audit_ip_created,

    DROP INDEX idx_audit_department_created,

    DROP COLUMN user_agent,

    DROP COLUMN department_id,

    DROP COLUMN severity,

    DROP COLUMN event_type;
