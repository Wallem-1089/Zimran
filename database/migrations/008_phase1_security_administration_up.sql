/*
|--------------------------------------------------------------------------
| Phase 1 - Milestone 1.4 Security Administration
|--------------------------------------------------------------------------
*/

ALTER TABLE audit_logs

    ADD COLUMN user_agent VARCHAR(255) NULL
        AFTER ip_address,

    ADD COLUMN department_id INT NULL
        AFTER user_agent,

    ADD COLUMN severity VARCHAR(20) NOT NULL DEFAULT 'INFO'
        AFTER department_id,

    ADD COLUMN event_type VARCHAR(100) NOT NULL DEFAULT 'GENERAL'
        AFTER severity,

    ADD INDEX idx_audit_action_created (action, created_at),

    ADD INDEX idx_audit_user_created (user_id, created_at),

    ADD INDEX idx_audit_ip_created (ip_address, created_at),

    ADD INDEX idx_audit_department_created (department_id, created_at),

    ADD CONSTRAINT fk_audit_department
        FOREIGN KEY (department_id)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;

CREATE TABLE active_sessions (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    session_id VARCHAR(128) NOT NULL,

    user_id INT NOT NULL,

    login_at DATETIME NOT NULL,

    last_activity DATETIME NOT NULL,

    expires_at DATETIME NOT NULL,

    ip_address VARCHAR(50) NULL,

    user_agent VARCHAR(255) NULL,

    active_department_id INT NULL,

    status ENUM('Active', 'Terminated', 'Expired') NOT NULL DEFAULT 'Active',

    terminated_at DATETIME NULL,

    terminated_by INT NULL,

    termination_reason VARCHAR(255) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_active_sessions_session UNIQUE (session_id),

    INDEX idx_sessions_user_status (user_id, status),

    INDEX idx_sessions_activity (status, last_activity),

    INDEX idx_sessions_expiry (status, expires_at),

    INDEX idx_sessions_department (active_department_id),

    CONSTRAINT fk_sessions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_sessions_department
        FOREIGN KEY (active_department_id)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_sessions_terminated_by
        FOREIGN KEY (terminated_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_history (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    password_hash VARCHAR(255) NOT NULL,

    change_type ENUM('Changed', 'Reset', 'Forced') NOT NULL,

    changed_by INT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_password_history_user_created (user_id, created_at),

    CONSTRAINT fk_password_history_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_password_history_changed_by
        FOREIGN KEY (changed_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
