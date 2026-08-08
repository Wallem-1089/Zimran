/*
|--------------------------------------------------------------------------
| Phase 1 - Milestone 1.1 User Management
|--------------------------------------------------------------------------
| Adds account-lock metadata without changing existing user semantics.
|--------------------------------------------------------------------------
*/

ALTER TABLE users

    ADD COLUMN locked_at DATETIME NULL
        AFTER last_failed_login,

    ADD COLUMN locked_by INT NULL
        AFTER locked_at,

    ADD COLUMN lock_reason VARCHAR(255) NULL
        AFTER locked_by,

    ADD INDEX idx_users_locked_at (locked_at),

    ADD CONSTRAINT fk_users_locked_by
        FOREIGN KEY (locked_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;
