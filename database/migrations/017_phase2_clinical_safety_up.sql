/* Phase 2 Milestone 2.3: longitudinal clinical safety. */

CREATE TABLE patient_allergies (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    source_visit_id INT NULL,
    allergy_type ENUM('Drug','Food','Environmental','Biological','Other') NOT NULL,
    substance VARCHAR(150) NOT NULL,
    normalized_substance VARCHAR(150) NOT NULL,
    active_allergy_key VARCHAR(512) NULL,
    reaction VARCHAR(500) NULL,
    severity ENUM('Mild','Moderate','Severe','Life-threatening','Unknown') NOT NULL DEFAULT 'Unknown',
    clinical_status ENUM('Active','Inactive','Resolved','Entered-in-error') NOT NULL DEFAULT 'Active',
    verification_status ENUM('Unverified','Confirmed','Refuted') NOT NULL DEFAULT 'Unverified',
    onset_date DATE NULL,
    recorded_by INT NOT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_by INT NULL,
    verified_at DATETIME NULL,
    resolved_by INT NULL,
    resolved_at DATETIME NULL,
    notes TEXT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_patient_allergy_active UNIQUE (active_allergy_key),
    INDEX idx_patient_allergies_patient_status (patient_id, clinical_status, severity),
    INDEX idx_patient_allergies_substance (normalized_substance, clinical_status),
    INDEX idx_patient_allergies_visit (source_visit_id),
    INDEX idx_patient_allergies_verification (verification_status, verified_at),
    CONSTRAINT fk_patient_allergies_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_allergies_visit FOREIGN KEY (source_visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_allergies_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_allergies_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_allergies_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_allergy_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    allergy_id BIGINT NOT NULL,
    patient_id INT NOT NULL,
    version_no INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    previous_snapshot LONGTEXT NULL,
    new_snapshot LONGTEXT NOT NULL,
    reason TEXT NOT NULL,
    changed_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_patient_allergy_history_version UNIQUE (allergy_id, version_no),
    INDEX idx_allergy_history_patient (patient_id, created_at),
    INDEX idx_allergy_history_actor (changed_by, created_at),
    CONSTRAINT fk_allergy_history_allergy FOREIGN KEY (allergy_id) REFERENCES patient_allergies(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_allergy_history_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_allergy_history_actor FOREIGN KEY (changed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_alerts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    visit_id INT NULL,
    alert_type ENUM('Clinical Risk','Infection Control','Fall Risk','Communication Need','Safeguarding','Special Handling','Other') NOT NULL,
    title VARCHAR(150) NOT NULL,
    normalized_title VARCHAR(150) NOT NULL,
    active_alert_key VARCHAR(512) NULL,
    reason TEXT NOT NULL,
    priority ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    confidentiality_level ENUM('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    starts_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_by INT NOT NULL,
    closed_by INT NULL,
    closed_at DATETIME NULL,
    closure_reason TEXT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_patient_alert_active UNIQUE (active_alert_key),
    INDEX idx_patient_alerts_patient_active (patient_id, is_active, priority),
    INDEX idx_patient_alerts_effective (patient_id, is_active, starts_at, expires_at),
    INDEX idx_patient_alerts_type_title (alert_type, normalized_title, is_active),
    INDEX idx_patient_alerts_visit (visit_id),
    INDEX idx_patient_alerts_confidentiality (confidentiality_level, is_active),
    CONSTRAINT fk_patient_alerts_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_alerts_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_alerts_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_alerts_closed_by FOREIGN KEY (closed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_alert_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    alert_id BIGINT NOT NULL,
    patient_id INT NOT NULL,
    version_no INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    previous_snapshot LONGTEXT NULL,
    new_snapshot LONGTEXT NOT NULL,
    reason TEXT NOT NULL,
    changed_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_patient_alert_history_version UNIQUE (alert_id, version_no),
    INDEX idx_alert_history_patient (patient_id, created_at),
    INDEX idx_alert_history_actor (changed_by, created_at),
    CONSTRAINT fk_alert_history_alert FOREIGN KEY (alert_id) REFERENCES patient_alerts(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_alert_history_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_alert_history_actor FOREIGN KEY (changed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description) VALUES
('view_clinical_safety','View Clinical Safety','Medical Records','View authorized longitudinal allergies and clinical alerts.'),
('record_allergies','Record Allergies','Medical Records','Record structured patient allergy information.'),
('update_allergies','Update Allergies','Medical Records','Correct active structured allergy information.'),
('verify_allergies','Verify Allergies','Medical Records','Clinically verify recorded allergy information.'),
('resolve_allergies','Resolve Allergies','Medical Records','Resolve or mark allergy records entered in error.'),
('manage_clinical_alerts','Manage Clinical Alerts','Medical Records','Create, update, close, and reactivate clinical alerts.'),
('view_confidential_alerts','View Confidential Alerts','Medical Records','View restricted and confidential clinical alert details.'),
('view_clinical_safety_history','View Clinical Safety History','Medical Records','View allergy and clinical alert version history.')
ON DUPLICATE KEY UPDATE permission_name=VALUES(permission_name), module=VALUES(module), description=VALUES(description), is_active=1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name IN ('Receptionist','Records Officer','Doctor','Nurse','Laboratory Scientist','Pharmacist','Physiotherapist','Radiographer','Theatre Staff')
AND p.permission_key='view_clinical_safety';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name IN ('Doctor','Nurse')
AND p.permission_key IN ('record_allergies','update_allergies','verify_allergies','manage_clinical_alerts','view_clinical_safety_history');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name='Doctor'
AND p.permission_key IN ('resolve_allergies','view_confidential_alerts');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name='Records Officer' AND p.permission_key='view_clinical_safety_history';

INSERT INTO system_settings (setting_key,setting_value,setting_type,setting_group,description,default_value,validation_rules,is_public,is_editable,is_system,sort_order) VALUES
('clinical_safety.allergy_types','["Drug","Food","Environmental","Biological","Other"]','array','Medical Records','Allowed structured allergy types.','["Drug","Food","Environmental","Biological","Other"]','{"required":true}',0,1,1,200),
('clinical_safety.severity_values','["Mild","Moderate","Severe","Life-threatening","Unknown"]','array','Medical Records','Allowed allergy severity values.','["Mild","Moderate","Severe","Life-threatening","Unknown"]','{"required":true}',0,1,1,210),
('clinical_safety.nurse_may_verify_allergies','false','boolean','Medical Records','Whether nurses with permission may confirm allergies.','false','{}',0,1,1,220),
('clinical_safety.alert_types','["Clinical Risk","Infection Control","Fall Risk","Communication Need","Safeguarding","Special Handling","Other"]','array','Medical Records','Allowed clinical alert types.','["Clinical Risk","Infection Control","Fall Risk","Communication Need","Safeguarding","Special Handling","Other"]','{"required":true}',0,1,1,230),
('clinical_safety.alert_priorities','["Low","Medium","High","Critical"]','array','Medical Records','Allowed clinical alert priorities.','["Low","Medium","High","Critical"]','{"required":true}',0,1,1,240),
('clinical_safety.confidentiality_levels','["Standard","Restricted","Confidential"]','array','Medical Records','Allowed alert confidentiality levels.','["Standard","Restricted","Confidential"]','{"required":true}',0,1,1,250),
('clinical_safety.default_alert_expiry_days','0','integer','Medical Records','Default alert lifetime in days; zero means none.','0','{"min":0,"max":3650}',0,1,1,260),
('clinical_safety.legacy_allergy_warning','true','boolean','Medical Records','Display legacy allergy text as an unverified warning.','true','{}',0,1,1,270)
ON DUPLICATE KEY UPDATE description=VALUES(description),default_value=VALUES(default_value),validation_rules=VALUES(validation_rules);
