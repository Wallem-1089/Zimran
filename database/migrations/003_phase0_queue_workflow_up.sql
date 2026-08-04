/*
|--------------------------------------------------------------------------
| Phase 0 - Milestone 0.4 Queue Workflow
|--------------------------------------------------------------------------
|
| Extends the existing visit_queue table. department_id remains the
| authoritative queue owner; no duplicate department ownership column is
| introduced.
|
*/

ALTER TABLE visit_queue

    MODIFY queue_status ENUM(
        'Waiting',
        'Called',
        'In Progress',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Waiting',

    ADD COLUMN called_at DATETIME NULL
        AFTER queued_at,

    ADD COLUMN started_at DATETIME NULL
        AFTER called_at,

    ADD COLUMN completed_at DATETIME NULL
        AFTER started_at,

    ADD COLUMN cancelled_at DATETIME NULL
        AFTER completed_at,

    ADD COLUMN assigned_user_id INT NULL
        AFTER department_id,

    ADD COLUMN position INT NULL
        AFTER assigned_user_id,

    ADD COLUMN remarks TEXT NULL
        AFTER position,

    ADD INDEX idx_queue_department_status_position
        (department_id, queue_status, position, queued_at),

    ADD INDEX idx_queue_visit_status
        (visit_id, queue_status),

    ADD INDEX idx_queue_queued_at
        (queued_at),

    ADD INDEX idx_queue_position
        (position),

    ADD CONSTRAINT fk_queue_assigned_user
        FOREIGN KEY (assigned_user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;

