/*
|--------------------------------------------------------------------------
| Phase 1 - Milestone 1.3 Departments and User Assignments Rollback
|--------------------------------------------------------------------------
*/

DROP TABLE user_departments;

ALTER TABLE departments

    DROP INDEX uq_departments_code,

    DROP INDEX idx_departments_active,

    DROP INDEX idx_departments_type,

    DROP INDEX idx_departments_queue,

    DROP COLUMN department_code,

    DROP COLUMN location,

    DROP COLUMN contact_extension,

    DROP COLUMN department_type,

    DROP COLUMN queue_enabled,

    DROP COLUMN is_active,

    DROP COLUMN display_order;
