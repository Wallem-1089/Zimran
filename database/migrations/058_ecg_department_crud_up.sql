-- ECG department, role, request/report workflow.

ALTER TABLE visits
    MODIFY visit_status ENUM(
        'Waiting','Reception','Records','Nursing','Doctor','Laboratory','X-Ray',
        'ECG','Pharmacy','Physiotherapy','Theatre','Accounts','Store',
        'Completed','Cancelled'
    ) NOT NULL DEFAULT 'Waiting';

INSERT INTO departments (
    department_name, department_code, description, department_type,
    queue_enabled, is_active, display_order
)
SELECT 'ECG', 'ECG', 'Electrocardiography services', 'Diagnostic', 1, 1, 65
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name = 'ECG');

INSERT INTO roles (role_name, description, is_active)
SELECT 'ECG Technician', 'ECG personnel for ECG request processing and chart upload', 1
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'ECG Technician');

CREATE TABLE IF NOT EXISTS ecg_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    requested_by INT NOT NULL,
    department_id INT NULL,
    request_source ENUM('Clinical','Direct') NOT NULL DEFAULT 'Clinical',
    study_requested VARCHAR(255) NOT NULL DEFAULT 'ECG',
    clinical_indication TEXT NULL,
    priority ENUM('Routine','Urgent') NOT NULL DEFAULT 'Routine',
    status ENUM('Requested','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_ecg_requests_visit_created (visit_id, created_at),
    INDEX idx_ecg_requests_patient_created (patient_id, created_at),
    INDEX idx_ecg_requests_status_created (status, created_at),
    INDEX idx_ecg_requests_department_status (department_id, status),
    INDEX idx_ecg_requests_requested_by (requested_by),
    CONSTRAINT fk_ecg_requests_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_requests_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_requests_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_requests_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ecg_reports (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ecg_request_id BIGINT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    chart_original_name VARCHAR(255) NULL,
    chart_stored_path VARCHAR(500) NULL,
    chart_mime_type VARCHAR(120) NULL,
    chart_file_size BIGINT NULL,
    notes TEXT NULL,
    remarks TEXT NULL,
    performed_by INT NOT NULL,
    updated_by INT NULL,
    completed_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_ecg_reports_request (ecg_request_id),
    INDEX idx_ecg_reports_visit_created (visit_id, created_at),
    INDEX idx_ecg_reports_patient_created (patient_id, created_at),
    INDEX idx_ecg_reports_performed_by (performed_by),
    CONSTRAINT fk_ecg_reports_request FOREIGN KEY (ecg_request_id) REFERENCES ecg_requests(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_reports_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_reports_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_reports_performed_by FOREIGN KEY (performed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_reports_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ecg_reports_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_ecg', 'View ECG', 'ECG', 'View ECG requests and reports.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_ecg');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_ecg_request', 'Create ECG Request', 'ECG', 'Create a clinical or direct ECG request.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_ecg_request');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'process_ecg_request', 'Process ECG Request', 'ECG', 'Start and process ECG requests.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'process_ecg_request');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'upload_ecg_chart', 'Upload ECG Chart', 'ECG', 'Upload scanned ECG charts and add notes.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'upload_ecg_chart');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'edit_ecg_report', 'Edit ECG Report', 'ECG', 'Edit ECG chart notes and remarks before completion.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'edit_ecg_report');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'complete_ecg_request', 'Complete ECG Request', 'ECG', 'Complete ECG requests after chart upload/reporting.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'complete_ecg_request');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'ECG Technician'
  AND p.permission_key IN (
      'view_ecg','create_ecg_request','process_ecg_request',
      'upload_ecg_chart','edit_ecg_report','complete_ecg_request',
      'view_medical_record'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key IN ('view_ecg','create_ecg_request');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Nurse','Records Officer','Laboratory Scientist','Radiographer','Physiotherapist','Theatre Staff','Pharmacist')
  AND p.permission_key = 'view_ecg';
