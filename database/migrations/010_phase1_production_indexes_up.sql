/*
|--------------------------------------------------------------------------
| Phase 1 - Milestone 1.7 Production Query Indexes
|--------------------------------------------------------------------------
*/

ALTER TABLE audit_logs

    ADD INDEX idx_audit_module_created (module, created_at),

    ADD INDEX idx_audit_event_created (event_type, created_at),

    ADD INDEX idx_audit_severity_created (severity, created_at),

    ADD INDEX idx_audit_visit_created (visit_id, created_at);
