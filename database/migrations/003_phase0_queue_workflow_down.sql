/*
|--------------------------------------------------------------------------
| Phase 0 - Milestone 0.4 Queue Workflow Rollback
|--------------------------------------------------------------------------
*/

ALTER TABLE visit_queue

    DROP FOREIGN KEY fk_queue_assigned_user,

    DROP INDEX idx_queue_department_status_position,

    DROP INDEX idx_queue_visit_status,

    DROP INDEX idx_queue_queued_at,

    DROP INDEX idx_queue_position,

    DROP COLUMN called_at,

    DROP COLUMN started_at,

    DROP COLUMN completed_at,

    DROP COLUMN cancelled_at,

    DROP COLUMN assigned_user_id,

    DROP COLUMN position,

    DROP COLUMN remarks,

    MODIFY queue_status ENUM(
        'Waiting',
        'In Progress',
        'Completed'
    ) NOT NULL DEFAULT 'Waiting';

