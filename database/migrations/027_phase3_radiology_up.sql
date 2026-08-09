/* Phase 3.5: Radiology CRUD. */

CREATE TABLE IF NOT EXISTS radiology_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    requested_by INT NOT NULL,
    department_id INT NULL,
    request_source ENUM('Clinical','Direct') NOT NULL DEFAULT 'Clinical',
    study_requested TEXT NOT NULL,
    clinical_indication TEXT NULL,
    priority ENUM('Routine','Urgent') NOT NULL DEFAULT 'Routine',
    status ENUM('Requested','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_radiology_requests_visit_created (visit_id, created_at),
    INDEX idx_radiology_requests_patient_created (patient_id, created_at),
    INDEX idx_radiology_requests_department_created (department_id, created_at),
    INDEX idx_radiology_requests_status_created (status, created_at),
    INDEX idx_radiology_requests_source_created (request_source, created_at),
    INDEX idx_radiology_requests_requested_by_created (requested_by, created_at),
    CONSTRAINT fk_radiology_requests_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_requests_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_requests_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_requests_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS radiology_reports (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    radiology_request_id BIGINT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    findings TEXT NULL,
    impression TEXT NOT NULL,
    recommendation TEXT NULL,
    performed_by INT NOT NULL,
    completed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_radiology_reports_request (radiology_request_id),
    INDEX idx_radiology_reports_visit_created (visit_id, created_at),
    INDEX idx_radiology_reports_patient_created (patient_id, created_at),
    INDEX idx_radiology_reports_performed_by_created (performed_by, created_at),
    INDEX idx_radiology_reports_completed_at (completed_at),
    CONSTRAINT fk_radiology_reports_request FOREIGN KEY (radiology_request_id) REFERENCES radiology_requests(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_reports_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_reports_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_reports_performed_by FOREIGN KEY (performed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_reports_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description) VALUES
('view_radiology', 'View Radiology', 'Radiology', 'View radiology requests and reports.'),
('create_radiology_request', 'Create Radiology Request', 'Radiology', 'Create a radiology request for an encounter.'),
('process_radiology_request', 'Process Radiology Request', 'Radiology', 'Start and process radiology requests.'),
('enter_radiology_report', 'Enter Radiology Report', 'Radiology', 'Enter a radiology report.'),
('edit_radiology_report', 'Edit Radiology Report', 'Radiology', 'Edit a radiology report.'),
('complete_radiology_request', 'Complete Radiology Request', 'Radiology', 'Complete a radiology request.')
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Radiographer'
  AND p.permission_key IN (
      'view_radiology',
      'create_radiology_request',
      'process_radiology_request',
      'enter_radiology_report',
      'edit_radiology_report',
      'complete_radiology_request'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key IN (
      'view_radiology',
      'create_radiology_request'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Nurse'
  AND p.permission_key = 'view_radiology';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Records Officer'
  AND p.permission_key = 'view_radiology';
