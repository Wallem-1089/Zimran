CREATE TABLE IF NOT EXISTS form_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_key VARCHAR(100) NOT NULL,
    form_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_form_definitions_key (form_key),
    CONSTRAINT fk_form_definitions_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_form_definitions_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS form_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_definition_id INT NOT NULL,
    field_key VARCHAR(100) NOT NULL,
    field_label VARCHAR(150) NOT NULL,
    field_type ENUM('text','textarea','number','date','select','checkbox','yes_no') NOT NULL DEFAULT 'text',
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    options_json TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_form_fields_key (form_definition_id, field_key),
    INDEX idx_form_fields_active (form_definition_id, is_active, sort_order),
    CONSTRAINT fk_form_fields_definition
        FOREIGN KEY (form_definition_id) REFERENCES form_definitions(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_form_fields_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_form_fields_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS form_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_key VARCHAR(100) NOT NULL,
    patient_id INT NOT NULL,
    visit_id INT NULL,
    source_module VARCHAR(80) NOT NULL,
    source_record_id INT NOT NULL,
    created_by INT NOT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_form_response_source (form_key, source_module, source_record_id),
    INDEX idx_form_responses_patient (patient_id, created_at),
    INDEX idx_form_responses_visit (visit_id, created_at),
    CONSTRAINT fk_form_responses_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_form_responses_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_form_responses_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_form_responses_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS form_response_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_response_id INT NOT NULL,
    form_field_id INT NOT NULL,
    field_key VARCHAR(100) NOT NULL,
    value_text TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_form_response_field (form_response_id, form_field_id),
    INDEX idx_form_response_values_key (field_key),
    CONSTRAINT fk_form_response_values_response
        FOREIGN KEY (form_response_id) REFERENCES form_responses(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_form_response_values_field
        FOREIGN KEY (form_field_id) REFERENCES form_fields(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

INSERT INTO form_definitions (
    form_key, form_name, description, is_active
)
SELECT
    'nursing_assessment',
    'Nursing Assessment',
    'Optional hospital-configured extra fields shown below the coded Nursing Assessment fields.',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM form_definitions WHERE form_key = 'nursing_assessment'
);

INSERT INTO form_fields (
    form_definition_id, field_key, field_label, field_type, is_required, sort_order, is_active
)
SELECT fd.id, seed.field_key, seed.field_label, 'textarea', 0, seed.sort_order, 0
FROM form_definitions fd
INNER JOIN (
    SELECT 'mental_status' AS field_key, 'Mental Status' AS field_label, 10 AS sort_order
    UNION ALL SELECT 'fall_prevention_advice', 'Fall Prevention Advice', 20
    UNION ALL SELECT 'patient_education_given', 'Patient Education Given', 30
) seed
WHERE fd.form_key = 'nursing_assessment'
  AND NOT EXISTS (
      SELECT 1
      FROM form_fields ff
      WHERE ff.form_definition_id = fd.id
        AND ff.field_key = seed.field_key
  );

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'manage_configurable_forms', 'Manage Configurable Forms', 'Administration', 'Add, edit, activate, and deactivate optional configured form fields.', 1
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE permission_key = 'manage_configurable_forms'
);

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_configurable_form_responses', 'View Configurable Form Responses', 'Administration', 'View optional configured field responses attached to clinical records.', 1
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE permission_key = 'view_configurable_form_responses'
);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
    ON p.permission_key IN ('manage_configurable_forms', 'view_configurable_form_responses')
WHERE r.role_name IN ('Super Administrator', 'System Administrator');
