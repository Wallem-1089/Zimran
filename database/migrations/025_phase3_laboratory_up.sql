/* Phase 3.4: Laboratory CRUD. */

CREATE TABLE IF NOT EXISTS laboratory_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    requested_by INT NOT NULL,
    department_id INT NULL,
    request_source ENUM('Clinical','Direct') NOT NULL DEFAULT 'Clinical',
    tests_requested TEXT NOT NULL,
    clinical_information TEXT NULL,
    priority ENUM('Routine','Urgent') NOT NULL DEFAULT 'Routine',
    status ENUM('Requested','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_laboratory_requests_visit_created (visit_id, created_at),
    INDEX idx_laboratory_requests_patient_created (patient_id, created_at),
    INDEX idx_laboratory_requests_department_created (department_id, created_at),
    INDEX idx_laboratory_requests_status_created (status, created_at),
    INDEX idx_laboratory_requests_source_created (request_source, created_at),
    INDEX idx_laboratory_requests_requested_by_created (requested_by, created_at),
    CONSTRAINT fk_laboratory_requests_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_requests_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_requests_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_requests_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS laboratory_results (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    laboratory_request_id BIGINT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    result TEXT NOT NULL,
    interpretation TEXT NULL,
    performed_by INT NOT NULL,
    completed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_laboratory_results_request (laboratory_request_id),
    INDEX idx_laboratory_results_visit_created (visit_id, created_at),
    INDEX idx_laboratory_results_patient_created (patient_id, created_at),
    INDEX idx_laboratory_results_performed_by_created (performed_by, created_at),
    INDEX idx_laboratory_results_completed_at (completed_at),
    CONSTRAINT fk_laboratory_results_request FOREIGN KEY (laboratory_request_id) REFERENCES laboratory_requests(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_results_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_results_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_results_performed_by FOREIGN KEY (performed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_results_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description) VALUES
('view_laboratory', 'View Laboratory', 'Laboratory', 'View laboratory requests and results.'),
('create_laboratory_request', 'Create Laboratory Request', 'Laboratory', 'Create a laboratory request for an encounter.'),
('process_laboratory_request', 'Process Laboratory Request', 'Laboratory', 'Start and process laboratory requests.'),
('enter_laboratory_result', 'Enter Laboratory Result', 'Laboratory', 'Enter a laboratory result.'),
('edit_laboratory_result', 'Edit Laboratory Result', 'Laboratory', 'Edit a laboratory result.'),
('complete_laboratory_request', 'Complete Laboratory Request', 'Laboratory', 'Complete a laboratory request.')
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Laboratory Scientist'
  AND p.permission_key IN (
      'view_laboratory',
      'create_laboratory_request',
      'process_laboratory_request',
      'enter_laboratory_result',
      'edit_laboratory_result',
      'complete_laboratory_request'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key IN (
      'view_laboratory',
      'create_laboratory_request'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Nurse'
  AND p.permission_key = 'view_laboratory';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Records Officer'
  AND p.permission_key = 'view_laboratory';
