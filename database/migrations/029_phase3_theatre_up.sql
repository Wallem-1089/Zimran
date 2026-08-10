SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS theatre_records (
    id INT NOT NULL AUTO_INCREMENT,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    surgeon_id INT NULL,
    department_id INT NULL,
    procedure_name VARCHAR(255) NOT NULL,
    indication TEXT NULL,
    preoperative_notes TEXT NULL,
    procedure_details LONGTEXT NOT NULL,
    findings TEXT NULL,
    complications TEXT NULL,
    postoperative_notes TEXT NULL,
    postoperative_plan TEXT NULL,
    anaesthesia_notes TEXT NULL,
    status ENUM('Draft','Completed') NOT NULL DEFAULT 'Draft',
    created_by INT NOT NULL,
    updated_by INT NULL,
    completed_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_theatre_records_visit (visit_id),
    KEY idx_theatre_records_patient (patient_id),
    KEY idx_theatre_records_surgeon (surgeon_id),
    KEY idx_theatre_records_department (department_id),
    KEY idx_theatre_records_status (status),
    KEY idx_theatre_records_created_at (created_at),
    KEY idx_theatre_records_created_by (created_by),
    KEY idx_theatre_records_completed_by (completed_by),
    CONSTRAINT fk_theatre_records_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_theatre_records_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_theatre_records_surgeon
        FOREIGN KEY (surgeon_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_theatre_records_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_theatre_records_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_theatre_records_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_theatre_records_completed_by
        FOREIGN KEY (completed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_theatre', 'View Theatre', 'Theatre', 'View theatre records and history.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_theatre');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_theatre', 'Create Theatre', 'Theatre', 'Create theatre records.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_theatre');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'edit_theatre', 'Edit Theatre', 'Theatre', 'Edit draft theatre records.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'edit_theatre');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'complete_theatre', 'Complete Theatre', 'Theatre', 'Complete theatre records.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'complete_theatre');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Doctor', 'Theatre Staff')
  AND p.permission_key IN ('view_theatre', 'create_theatre', 'edit_theatre', 'complete_theatre');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Nurse'
  AND p.permission_key = 'view_theatre';

SET FOREIGN_KEY_CHECKS = 1;
