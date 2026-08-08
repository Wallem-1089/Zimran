/* Phase 3.2: Vital Signs CRUD. */

CREATE TABLE IF NOT EXISTS vital_signs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    department_id INT NULL,
    recorded_by INT NOT NULL,
    temperature DECIMAL(5,2) NULL,
    pulse INT NULL,
    respiratory_rate INT NULL,
    systolic_bp INT NULL,
    diastolic_bp INT NULL,
    oxygen_saturation DECIMAL(5,2) NULL,
    weight DECIMAL(6,2) NULL,
    height DECIMAL(6,2) NULL,
    bmi DECIMAL(6,2) NULL,
    blood_glucose DECIMAL(7,2) NULL,
    pain_score TINYINT NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vital_signs_visit_created (visit_id, created_at),
    INDEX idx_vital_signs_patient_created (patient_id, created_at),
    INDEX idx_vital_signs_recorded_by_created (recorded_by, created_at),
    INDEX idx_vital_signs_department_created (department_id, created_at),
    CONSTRAINT fk_vital_signs_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_vital_signs_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_vital_signs_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_vital_signs_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description) VALUES
('view_vital_signs', 'View Vital Signs', 'Vital Signs', 'View patient and encounter vital signs.'),
('create_vital_signs', 'Create Vital Signs', 'Vital Signs', 'Record vital signs for an active encounter.'),
('edit_vital_signs', 'Edit Vital Signs', 'Vital Signs', 'Edit recorded vital signs.')
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
      'view_vital_signs',
      'create_vital_signs',
      'edit_vital_signs'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Nurse'
  AND p.permission_key IN (
      'view_vital_signs',
      'create_vital_signs',
      'edit_vital_signs'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Records Officer'
  AND p.permission_key = 'view_vital_signs';
