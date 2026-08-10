/* Phase 3.6: Physiotherapy CRUD. */

CREATE TABLE IF NOT EXISTS physiotherapy_records (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    physiotherapist_id INT NULL,
    department_id INT NULL,
    record_source ENUM('Clinical','Direct') NOT NULL DEFAULT 'Clinical',
    referral_reason TEXT NULL,
    presenting_problem TEXT NOT NULL,
    assessment TEXT NOT NULL,
    functional_limitations TEXT NULL,
    treatment_plan TEXT NOT NULL,
    goals TEXT NULL,
    precautions TEXT NULL,
    status ENUM('Active','Completed','Cancelled') NOT NULL DEFAULT 'Active',
    created_by INT NOT NULL,
    updated_by INT NULL,
    completed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_physiotherapy_records_visit (visit_id),
    INDEX idx_physiotherapy_records_patient_created (patient_id, created_at),
    INDEX idx_physiotherapy_records_physio_created (physiotherapist_id, created_at),
    INDEX idx_physiotherapy_records_department_created (department_id, created_at),
    INDEX idx_physiotherapy_records_source_created (record_source, created_at),
    INDEX idx_physiotherapy_records_status_created (status, created_at),
    INDEX idx_physiotherapy_records_creator_created (created_by, created_at),
    CONSTRAINT fk_physiotherapy_records_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_physiotherapy_records_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_physiotherapy_records_physio FOREIGN KEY (physiotherapist_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_physiotherapy_records_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_physiotherapy_records_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_physiotherapy_records_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_physiotherapy_records_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS physiotherapy_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    physiotherapy_record_id BIGINT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    session_date DATETIME NOT NULL,
    treatment_given TEXT NOT NULL,
    patient_response TEXT NULL,
    progress_notes TEXT NULL,
    next_plan TEXT NULL,
    recorded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_physiotherapy_sessions_record_date (physiotherapy_record_id, session_date),
    INDEX idx_physiotherapy_sessions_visit_date (visit_id, session_date),
    INDEX idx_physiotherapy_sessions_patient_date (patient_id, session_date),
    INDEX idx_physiotherapy_sessions_recorded_by_date (recorded_by, session_date),
    CONSTRAINT fk_physiotherapy_sessions_record FOREIGN KEY (physiotherapy_record_id) REFERENCES physiotherapy_records(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_physiotherapy_sessions_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_physiotherapy_sessions_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_physiotherapy_sessions_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description) VALUES
('view_physiotherapy', 'View Physiotherapy', 'Physiotherapy', 'View physiotherapy records and sessions.'),
('create_physiotherapy', 'Create Physiotherapy Record', 'Physiotherapy', 'Create a physiotherapy record for an encounter.'),
('edit_physiotherapy', 'Edit Physiotherapy Record', 'Physiotherapy', 'Edit a physiotherapy record.'),
('manage_physiotherapy_sessions', 'Manage Physiotherapy Sessions', 'Physiotherapy', 'Create and edit physiotherapy sessions.'),
('complete_physiotherapy', 'Complete Physiotherapy Record', 'Physiotherapy', 'Complete a physiotherapy record.')
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Physiotherapist'
  AND p.permission_key IN (
      'view_encounter',
      'view_physiotherapy',
      'create_physiotherapy',
      'edit_physiotherapy',
      'manage_physiotherapy_sessions',
      'complete_physiotherapy'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key IN (
      'view_physiotherapy',
      'create_physiotherapy'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Nurse'
  AND p.permission_key = 'view_physiotherapy';
