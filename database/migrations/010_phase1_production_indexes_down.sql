/*
|--------------------------------------------------------------------------
| Rollback Phase 1 - Milestone 1.7 Production Query Indexes
|--------------------------------------------------------------------------
*/

ALTER TABLE audit_logs

    DROP INDEX idx_audit_module_created,

    DROP INDEX idx_audit_event_created,

    DROP INDEX idx_audit_severity_created,

    DROP INDEX idx_audit_visit_created;
