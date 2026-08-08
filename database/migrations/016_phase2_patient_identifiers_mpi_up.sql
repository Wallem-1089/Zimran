/*
|--------------------------------------------------------------------------
| Phase 2 - Milestone 2.2 Patient Identifiers and MPI
|--------------------------------------------------------------------------
| Migration 014 was preserved during database recovery and may already own
| these structures. IF NOT EXISTS formally adopts the schema without replaying
| or replacing retained identifier and duplicate-review history.
*/

ALTER TABLE patients
    ADD COLUMN IF NOT EXISTS normalized_first_name VARCHAR(100) NULL
        AFTER first_name,
    ADD COLUMN IF NOT EXISTS normalized_middle_name VARCHAR(100) NULL
        AFTER middle_name,
    ADD COLUMN IF NOT EXISTS normalized_last_name VARCHAR(100) NULL
        AFTER last_name,
    ADD COLUMN IF NOT EXISTS normalized_phone VARCHAR(30) NULL
        AFTER phone,
    ADD COLUMN IF NOT EXISTS normalized_email VARCHAR(150) NULL
        AFTER email;

UPDATE patients
SET normalized_first_name = LOWER(TRIM(first_name)),
    normalized_middle_name = NULLIF(LOWER(TRIM(middle_name)), ''),
    normalized_last_name = LOWER(TRIM(last_name)),
    normalized_phone = NULLIF(
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
            TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''),
        ''
    ),
    normalized_email = NULLIF(LOWER(TRIM(email)), '')
WHERE normalized_first_name IS NULL
   OR normalized_last_name IS NULL
   OR (phone IS NOT NULL AND normalized_phone IS NULL)
   OR (email IS NOT NULL AND normalized_email IS NULL);

ALTER TABLE patients
    ADD INDEX IF NOT EXISTS idx_patients_normalized_name
        (normalized_last_name, normalized_first_name, date_of_birth),
    ADD INDEX IF NOT EXISTS idx_patients_normalized_phone
        (normalized_phone),
    ADD INDEX IF NOT EXISTS idx_patients_normalized_email
        (normalized_email),
    ADD INDEX IF NOT EXISTS idx_patients_dob_normalized_name
        (date_of_birth, normalized_last_name, normalized_first_name);

CREATE TABLE IF NOT EXISTS patient_identifiers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    identifier_type VARCHAR(80) NOT NULL,
    identifier_value VARCHAR(255) NOT NULL,
    normalized_value VARCHAR(255) NOT NULL,
    issuing_authority VARCHAR(150) NULL,
    issuing_authority_key VARCHAR(150) NOT NULL DEFAULT '',
    uniqueness_scope ENUM('Global','Authority','Patient','None')
        NOT NULL DEFAULT 'Patient',
    uniqueness_key VARCHAR(512) NULL,
    issue_date DATE NULL,
    expiry_date DATE NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    primary_key_value VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    verification_status ENUM('Unverified','Verified','Rejected')
        NOT NULL DEFAULT 'Unverified',
    verified_by INT NULL,
    verified_at DATETIME NULL,
    created_by INT NOT NULL,
    updated_by INT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_patient_identifier_uniqueness UNIQUE (uniqueness_key),
    CONSTRAINT uq_patient_identifier_primary UNIQUE (primary_key_value),
    INDEX idx_patient_identifiers_patient
        (patient_id, is_active, identifier_type),
    INDEX idx_patient_identifiers_lookup
        (identifier_type, normalized_value, is_active),
    INDEX idx_patient_identifiers_authority
        (identifier_type, issuing_authority_key, normalized_value),
    INDEX idx_patient_identifiers_verification
        (verification_status, verified_at),
    CONSTRAINT fk_patient_identifiers_patient FOREIGN KEY (patient_id)
        REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_identifiers_verified_by FOREIGN KEY (verified_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_identifiers_created_by FOREIGN KEY (created_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_identifiers_updated_by FOREIGN KEY (updated_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_identifier_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    identifier_id BIGINT NOT NULL,
    patient_id INT NOT NULL,
    version_no INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    previous_snapshot LONGTEXT NULL,
    new_snapshot LONGTEXT NOT NULL,
    reason TEXT NOT NULL,
    changed_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_patient_identifier_history_version
        UNIQUE (identifier_id, version_no),
    INDEX idx_identifier_history_patient (patient_id, created_at),
    INDEX idx_identifier_history_actor (changed_by, created_at),
    CONSTRAINT fk_identifier_history_identifier FOREIGN KEY (identifier_id)
        REFERENCES patient_identifiers(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_identifier_history_patient FOREIGN KEY (patient_id)
        REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_identifier_history_actor FOREIGN KEY (changed_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_duplicate_candidates (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id_low INT NOT NULL,
    patient_id_high INT NOT NULL,
    match_score DECIMAL(5,2) NOT NULL,
    classification ENUM(
        'Exact Match','Strong Possible Match','Possible Match','Low Confidence'
    ) NOT NULL,
    matched_factors LONGTEXT NOT NULL,
    status ENUM(
        'Pending','Confirmed Duplicate','Not Duplicate','Deferred','Merge Requested'
    ) NOT NULL DEFAULT 'Pending',
    review_decision VARCHAR(100) NULL,
    review_reason TEXT NULL,
    detected_by INT NULL,
    detected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_duplicate_candidate_pair UNIQUE (patient_id_low, patient_id_high),
    CONSTRAINT chk_duplicate_candidate_order CHECK (patient_id_low < patient_id_high),
    INDEX idx_duplicate_candidates_status
        (status, classification, detected_at),
    INDEX idx_duplicate_candidates_low (patient_id_low, status),
    INDEX idx_duplicate_candidates_high (patient_id_high, status),
    CONSTRAINT fk_duplicate_candidates_low FOREIGN KEY (patient_id_low)
        REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_duplicate_candidates_high FOREIGN KEY (patient_id_high)
        REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_duplicate_candidates_detected_by FOREIGN KEY (detected_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_duplicate_candidates_reviewed_by FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description)
VALUES
    ('manage_patient_identifiers', 'Manage Patient Identifiers', 'Medical Records', 'Create, amend, deactivate, and select primary patient identifiers.'),
    ('view_duplicate_candidates', 'View Duplicate Candidates', 'Medical Records', 'View possible duplicate patient cases.'),
    ('review_duplicate_candidates', 'Review Duplicate Candidates', 'Medical Records', 'Record a controlled duplicate-case review decision.')
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name = 'Records Officer'
  AND p.permission_key IN (
      'manage_patient_identifiers',
      'view_duplicate_candidates',
      'review_duplicate_candidates'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name = 'Receptionist'
  AND p.permission_key IN (
      'manage_patient_identifiers',
      'view_duplicate_candidates'
  );

INSERT INTO system_settings (
    setting_key, setting_value, setting_type, setting_group, description,
    default_value, validation_rules, is_public, is_editable, is_system,
    sort_order
) VALUES
    ('mpi.identifier_definitions', '["National Identification Number","Insurance Number","Passport Number","External Hospital Number","Legacy Medical Record Number"]', 'array', 'Medical Records', 'Approved alternate patient identifier definitions.', '["National Identification Number","Insurance Number","Passport Number","External Hospital Number","Legacy Medical Record Number"]', '{"required":true}', 0, 1, 1, 90),
    ('mpi.duplicate_threshold', '55', 'integer', 'Medical Records', 'Minimum score that creates a duplicate review warning.', '55', '{"min":1,"max":100}', 0, 1, 1, 100),
    ('mpi.fuzzy_search_threshold', '70', 'integer', 'Medical Records', 'Minimum bounded fuzzy-name similarity percentage.', '70', '{"min":50,"max":100}', 0, 1, 1, 110),
    ('mpi.exact_match_priority', 'true', 'boolean', 'Medical Records', 'Rank exact identifiers before prefix and bounded fuzzy results.', 'true', '{}', 0, 1, 1, 120)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    default_value = VALUES(default_value),
    validation_rules = VALUES(validation_rules);
