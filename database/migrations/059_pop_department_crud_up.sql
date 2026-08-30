-- POP department, role, request/procedure workflow.

ALTER TABLE visits
    MODIFY visit_status ENUM(
        'Waiting','Reception','Records','Nursing','Doctor','Laboratory','X-Ray',
        'ECG','POP','Pharmacy','Physiotherapy','Theatre','Accounts','Store',
        'Completed','Cancelled'
    ) NOT NULL DEFAULT 'Waiting';

INSERT INTO departments (
    department_name, department_code, description, department_type,
    queue_enabled, is_active, display_order
)
SELECT 'POP', 'POP', 'Plaster of Paris and casting services', 'Clinical', 1, 1, 66
WHERE NOT EXISTS (SELECT 1 FROM departments WHERE department_name = 'POP');

INSERT INTO roles (role_name, description, is_active)
SELECT 'POP Technician', 'POP/casting personnel for request processing and procedure documentation', 1
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = 'POP Technician');

CREATE TABLE IF NOT EXISTS pop_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    requested_by INT NOT NULL,
    department_id INT NULL,
    request_source ENUM('Clinical','Direct') NOT NULL DEFAULT 'Clinical',
    procedure_requested VARCHAR(255) NOT NULL DEFAULT 'POP / Casting',
    clinical_indication TEXT NULL,
    priority ENUM('Routine','Urgent') NOT NULL DEFAULT 'Routine',
    status ENUM('Requested','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_pop_requests_visit_created (visit_id, created_at),
    INDEX idx_pop_requests_patient_created (patient_id, created_at),
    INDEX idx_pop_requests_status_created (status, created_at),
    INDEX idx_pop_requests_department_status (department_id, status),
    INDEX idx_pop_requests_requested_by (requested_by),
    CONSTRAINT fk_pop_requests_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pop_requests_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pop_requests_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pop_requests_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pop_records (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    pop_request_id BIGINT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    cast_type VARCHAR(255) NULL,
    body_part VARCHAR(255) NULL,
    procedure_notes TEXT NOT NULL,
    materials_used TEXT NULL,
    aftercare_instructions TEXT NULL,
    remarks TEXT NULL,
    performed_by INT NOT NULL,
    updated_by INT NULL,
    completed_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_pop_records_request (pop_request_id),
    INDEX idx_pop_records_visit_created (visit_id, created_at),
    INDEX idx_pop_records_patient_created (patient_id, created_at),
    INDEX idx_pop_records_performed_by (performed_by),
    CONSTRAINT fk_pop_records_request FOREIGN KEY (pop_request_id) REFERENCES pop_requests(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pop_records_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pop_records_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pop_records_performed_by FOREIGN KEY (performed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pop_records_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_pop_records_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_pop', 'View POP', 'POP', 'View POP requests and casting/procedure records.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_pop');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_pop_request', 'Create POP Request', 'POP', 'Create a clinical or direct POP/casting request.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_pop_request');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'process_pop_request', 'Process POP Request', 'POP', 'Start and process POP/casting requests.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'process_pop_request');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'record_pop_procedure', 'Record POP Procedure', 'POP', 'Document POP/casting procedure details.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'record_pop_procedure');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'edit_pop_record', 'Edit POP Record', 'POP', 'Edit POP/casting records before completion.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'edit_pop_record');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'complete_pop_request', 'Complete POP Request', 'POP', 'Complete POP/casting requests after procedure documentation.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'complete_pop_request');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'POP Technician'
  AND p.permission_key IN (
      'view_pop','create_pop_request','process_pop_request',
      'record_pop_procedure','edit_pop_record','complete_pop_request',
      'view_medical_record'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key IN ('view_pop','create_pop_request');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Nurse','Records Officer','Laboratory Scientist','Radiographer','ECG Technician','Physiotherapist','Theatre Staff','Pharmacist')
  AND p.permission_key = 'view_pop';
