CREATE TABLE IF NOT EXISTS consultations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    department_id INT NULL,
    presenting_complaint TEXT NOT NULL,
    history_of_presenting_complaint TEXT NOT NULL,
    examination_findings TEXT NOT NULL,
    assessment TEXT NOT NULL,
    diagnosis TEXT NOT NULL,
    treatment_plan TEXT NOT NULL,
    advice TEXT NULL,
    follow_up TEXT NULL,
    referral_notes TEXT NULL,
    status ENUM('Draft','Completed') NOT NULL DEFAULT 'Draft',
    created_by INT NOT NULL,
    updated_by INT NULL,
    completed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_consultations_visit (visit_id),
    INDEX idx_consultations_patient_status (patient_id, status, created_at),
    INDEX idx_consultations_doctor_status (doctor_id, status, created_at),
    INDEX idx_consultations_department (department_id, created_at),
    CONSTRAINT fk_consultations_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_consultations_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS department_notifications (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    from_department_id INT NULL,
    to_department_id INT NOT NULL,
    sent_by INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Unread','Read','Resolved') NOT NULL DEFAULT 'Unread',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_by INT NULL,
    read_at DATETIME NULL,
    resolved_by INT NULL,
    resolved_at DATETIME NULL,
    INDEX idx_department_notifications_to_status (to_department_id, status, created_at),
    INDEX idx_department_notifications_visit (visit_id, created_at),
    INDEX idx_department_notifications_patient (patient_id, created_at),
    INDEX idx_department_notifications_sender (sent_by, created_at),
    CONSTRAINT fk_department_notifications_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_department_notifications_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_department_notifications_from_department FOREIGN KEY (from_department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_department_notifications_to_department FOREIGN KEY (to_department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_department_notifications_sent_by FOREIGN KEY (sent_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_department_notifications_read_by FOREIGN KEY (read_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_department_notifications_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description) VALUES
('view_consultation', 'View Consultation', 'Consultation', 'View encounter consultation records.'),
('create_consultation', 'Create Consultation', 'Consultation', 'Create a consultation for an active encounter.'),
('edit_consultation', 'Edit Consultation', 'Consultation', 'Edit draft consultation records.'),
('complete_consultation', 'Complete Consultation', 'Consultation', 'Complete a draft consultation.')
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key IN (
      'view_consultation',
      'create_consultation',
      'edit_consultation',
      'complete_consultation'
  );
