/* Phase 2 Milestone 2.4: longitudinal Problem List and structured history. */

CREATE TABLE IF NOT EXISTS patient_problems (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    source_visit_id INT NULL,
    problem_code_system VARCHAR(80) NULL,
    problem_code VARCHAR(80) NULL,
    problem_name VARCHAR(200) NOT NULL,
    normalized_problem_name VARCHAR(200) NOT NULL,
    category ENUM('Chronic Condition','Acute Problem','Historical Diagnosis','Surgical Condition','Risk Factor','Other') NOT NULL DEFAULT 'Other',
    clinical_status ENUM('Active','Inactive','Resolved','Entered-in-error') NOT NULL DEFAULT 'Active',
    verification_status ENUM('Unverified','Confirmed','Refuted') NOT NULL DEFAULT 'Unverified',
    severity ENUM('Mild','Moderate','Severe','Unknown') NOT NULL DEFAULT 'Unknown',
    confidentiality_level ENUM('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
    onset_date DATE NULL,
    recorded_date DATE NOT NULL,
    resolved_date DATE NULL,
    active_problem_key VARCHAR(512) NULL,
    recorded_by INT NOT NULL,
    verified_by INT NULL,
    verified_at DATETIME NULL,
    resolved_by INT NULL,
    notes TEXT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_patient_problem_active UNIQUE (active_problem_key),
    INDEX idx_patient_problems_status (patient_id, clinical_status, verification_status),
    INDEX idx_patient_problems_severity (patient_id, clinical_status, severity),
    INDEX idx_patient_problems_name (normalized_problem_name, clinical_status),
    INDEX idx_patient_problems_code (problem_code_system, problem_code),
    INDEX idx_patient_problems_visit (source_visit_id),
    INDEX idx_patient_problems_confidentiality (confidentiality_level, clinical_status),
    CONSTRAINT fk_patient_problems_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_problems_visit FOREIGN KEY (source_visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_problems_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_problems_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_problems_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_problem_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    problem_id BIGINT NOT NULL,
    patient_id INT NOT NULL,
    version_no INT NOT NULL,
    action VARCHAR(60) NOT NULL,
    previous_snapshot LONGTEXT NULL,
    new_snapshot LONGTEXT NOT NULL,
    reason TEXT NOT NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
    changed_by INT NOT NULL,
    department_id INT NULL,
    visit_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_patient_problem_history_version UNIQUE (problem_id, version_no),
    INDEX idx_problem_history_patient (patient_id, created_at),
    INDEX idx_problem_history_actor (changed_by, created_at),
    INDEX idx_problem_history_visit (visit_id, created_at),
    INDEX idx_problem_history_confidentiality (confidentiality_level, created_at),
    CONSTRAINT fk_problem_history_problem FOREIGN KEY (problem_id) REFERENCES patient_problems(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_problem_history_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_problem_history_actor FOREIGN KEY (changed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_problem_history_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_problem_history_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_medical_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    source_visit_id INT NULL,
    history_type ENUM('Past Medical History','Surgical History','Family History','Social History','Obstetric History','Immunization History','Previous Hospitalization','Previous Procedure','Other') NOT NULL,
    title VARCHAR(200) NOT NULL,
    normalized_title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NULL,
    date_precision ENUM('Exact','Month','Year','Unknown') NOT NULL DEFAULT 'Unknown',
    status ENUM('Active','Historical','Entered-in-error') NOT NULL DEFAULT 'Historical',
    source VARCHAR(150) NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
    recorded_by INT NOT NULL,
    verified_by INT NULL,
    verified_at DATETIME NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_medical_history_patient_type (patient_id, history_type, status),
    INDEX idx_medical_history_title (normalized_title, history_type),
    INDEX idx_medical_history_event (patient_id, event_date),
    INDEX idx_medical_history_visit (source_visit_id),
    INDEX idx_medical_history_confidentiality (confidentiality_level, status),
    CONSTRAINT fk_medical_history_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_visit FOREIGN KEY (source_visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_medical_history_versions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    history_entry_id BIGINT NOT NULL,
    patient_id INT NOT NULL,
    version_no INT NOT NULL,
    action VARCHAR(60) NOT NULL,
    previous_snapshot LONGTEXT NULL,
    new_snapshot LONGTEXT NOT NULL,
    reason TEXT NOT NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
    changed_by INT NOT NULL,
    department_id INT NULL,
    visit_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_medical_history_version UNIQUE (history_entry_id, version_no),
    INDEX idx_medical_history_versions_patient (patient_id, created_at),
    INDEX idx_medical_history_versions_actor (changed_by, created_at),
    INDEX idx_medical_history_versions_visit (visit_id, created_at),
    INDEX idx_medical_history_versions_confidentiality (confidentiality_level, created_at),
    CONSTRAINT fk_medical_history_versions_entry FOREIGN KEY (history_entry_id) REFERENCES patient_medical_history(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_versions_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_versions_actor FOREIGN KEY (changed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_versions_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_medical_history_versions_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key,permission_name,module,description) VALUES
('view_problem_list','View Problem List','Medical Records','View authorized longitudinal patient problems.'),
('manage_problem_list','Manage Problem List','Medical Records','Create and update longitudinal patient problems.'),
('verify_problem_list','Verify Problem List','Medical Records','Clinically verify or refute longitudinal problems.'),
('resolve_problem_list','Resolve Problem List','Medical Records','Resolve, deactivate, reactivate, or mark problems entered in error.'),
('view_medical_history','View Medical History','Medical Records','View structured longitudinal medical history.'),
('manage_medical_history','Manage Medical History','Medical Records','Create, update, and correct structured medical history.'),
('verify_medical_history','Verify Medical History','Medical Records','Clinically verify structured medical history.'),
('view_confidential_medical_history','View Confidential Medical History','Medical Records','View restricted problems and structured history details.'),
('view_problem_history','View Problem History','Medical Records','View problem and structured medical-history versions.')
ON DUPLICATE KEY UPDATE permission_name=VALUES(permission_name),module=VALUES(module),description=VALUES(description),is_active=1;

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name IN ('Records Officer','Doctor','Nurse','Laboratory Scientist','Pharmacist','Physiotherapist','Radiographer','Theatre Staff')
AND p.permission_key IN ('view_problem_list','view_medical_history');

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name='Doctor'
AND p.permission_key IN ('manage_problem_list','verify_problem_list','resolve_problem_list','manage_medical_history','verify_medical_history','view_confidential_medical_history','view_problem_history');

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name='Nurse'
AND p.permission_key IN ('manage_medical_history','view_problem_history');

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name='Records Officer' AND p.permission_key='view_problem_history';

INSERT INTO system_settings (setting_key,setting_value,setting_type,setting_group,description,default_value,validation_rules,is_public,is_editable,is_system,sort_order) VALUES
('problem_list.categories','["Chronic Condition","Acute Problem","Historical Diagnosis","Surgical Condition","Risk Factor","Other"]','array','Medical Records','Enabled Problem List categories.','["Chronic Condition","Acute Problem","Historical Diagnosis","Surgical Condition","Risk Factor","Other"]','{"required":true,"schema_values":["Chronic Condition","Acute Problem","Historical Diagnosis","Surgical Condition","Risk Factor","Other"]}',0,1,1,300),
('problem_list.severities','["Mild","Moderate","Severe","Unknown"]','array','Medical Records','Enabled Problem List severity values.','["Mild","Moderate","Severe","Unknown"]','{"required":true,"schema_values":["Mild","Moderate","Severe","Unknown"]}',0,1,1,310),
('problem_list.allow_self_verification','false','boolean','Medical Records','Whether the latest problem author may verify the same problem.','false','{}',0,1,1,320),
('problem_list.nurse_may_manage','false','boolean','Medical Records','Whether nurses with permission may manage longitudinal problems.','false','{}',0,1,1,330),
('problem_list.show_resolved_in_workspace','false','boolean','Medical Records','Whether resolved problems appear in Encounter Workspace summaries.','false','{}',0,1,1,340),
('medical_history.types','["Past Medical History","Surgical History","Family History","Social History","Obstetric History","Immunization History","Previous Hospitalization","Previous Procedure","Other"]','array','Medical Records','Enabled structured medical-history types.','["Past Medical History","Surgical History","Family History","Social History","Obstetric History","Immunization History","Previous Hospitalization","Previous Procedure","Other"]','{"required":true,"schema_values":["Past Medical History","Surgical History","Family History","Social History","Obstetric History","Immunization History","Previous Hospitalization","Previous Procedure","Other"]}',0,1,1,350),
('medical_history.confidentiality_levels','["Standard","Restricted","Confidential"]','array','Medical Records','Enabled confidentiality classifications for problems and medical history.','["Standard","Restricted","Confidential"]','{"required":true,"schema_values":["Standard","Restricted","Confidential"]}',0,1,1,360),
('medical_history.allow_self_verification','false','boolean','Medical Records','Whether the latest history author may verify the same entry.','false','{}',0,1,1,370)
ON DUPLICATE KEY UPDATE description=VALUES(description),default_value=VALUES(default_value),validation_rules=VALUES(validation_rules);
