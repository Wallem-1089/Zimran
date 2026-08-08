/* Phase 3.3: Nursing CRUD. */

CREATE TABLE IF NOT EXISTS nursing_assessments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    nurse_id INT NULL,
    department_id INT NULL,
    general_condition TEXT NULL,
    nursing_observation TEXT NULL,
    pain_assessment TEXT NULL,
    mobility TEXT NULL,
    nutrition TEXT NULL,
    elimination TEXT NULL,
    skin_assessment TEXT NULL,
    fall_risk TEXT NULL,
    nursing_interventions TEXT NULL,
    patient_response TEXT NULL,
    handover_notes TEXT NULL,
    additional_notes TEXT NULL,
    status ENUM('Draft','Completed') NOT NULL DEFAULT 'Draft',
    created_by INT NOT NULL,
    updated_by INT NULL,
    completed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_nursing_assessments_visit (visit_id),
    INDEX idx_nursing_assessments_patient_created (patient_id, created_at),
    INDEX idx_nursing_assessments_nurse_created (nurse_id, created_at),
    INDEX idx_nursing_assessments_department_created (department_id, created_at),
    INDEX idx_nursing_assessments_status_created (status, created_at),
    CONSTRAINT fk_nursing_assessments_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_nursing_assessments_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_nursing_assessments_nurse FOREIGN KEY (nurse_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_nursing_assessments_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_nursing_assessments_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_nursing_assessments_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_nursing_assessments_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description) VALUES
('view_nursing', 'View Nursing', 'Nursing', 'View nursing assessments and summaries.'),
('create_nursing', 'Create Nursing Assessment', 'Nursing', 'Start a nursing assessment for an active encounter.'),
('edit_nursing', 'Edit Nursing Assessment', 'Nursing', 'Edit a draft nursing assessment.'),
('complete_nursing', 'Complete Nursing Assessment', 'Nursing', 'Complete a draft nursing assessment.')
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Nurse'
  AND p.permission_key IN (
      'view_nursing',
      'create_nursing',
      'edit_nursing',
      'complete_nursing'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key = 'view_nursing';
