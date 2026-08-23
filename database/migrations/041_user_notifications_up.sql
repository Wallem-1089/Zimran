-- User-to-user encounter notifications.
-- These are attention requests only; they do not transfer encounter ownership.

CREATE TABLE IF NOT EXISTS user_notifications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    to_user_id INT NOT NULL,
    sent_by INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Unread','Read','Resolved') NOT NULL DEFAULT 'Unread',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_by INT NULL,
    read_at TIMESTAMP NULL,
    resolved_by INT NULL,
    resolved_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_user_notifications_to_status (to_user_id, status, created_at),
    INDEX idx_user_notifications_visit (visit_id, created_at),
    INDEX idx_user_notifications_patient (patient_id, created_at),
    INDEX idx_user_notifications_sent_by (sent_by, created_at),
    CONSTRAINT fk_user_notifications_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_user_notifications_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_user_notifications_to_user
        FOREIGN KEY (to_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_user_notifications_sent_by
        FOREIGN KEY (sent_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_user_notifications_read_by
        FOREIGN KEY (read_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_user_notifications_resolved_by
        FOREIGN KEY (resolved_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

