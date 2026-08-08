/*
|--------------------------------------------------------------------------
| Recovery Safety and Phase 2 Seed Reconciliation
|--------------------------------------------------------------------------
| The migration ledger is also created by guarded CLI tooling before status
| checks. IF NOT EXISTS keeps this migration compatible with that bootstrap.
*/

CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL,
    checksum CHAR(64) NOT NULL,
    batch INT NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    execution_time_ms INT NOT NULL DEFAULT 0,
    CONSTRAINT uq_schema_migrations_name UNIQUE (migration_name),
    INDEX idx_schema_migrations_batch (batch, applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description)
VALUES
    ('view_medical_record', 'View Medical Records', 'Medical Records', 'View an authorized patient longitudinal chart.'),
    ('edit_patient_demographics', 'Edit Patient Demographics', 'Medical Records', 'Correct patient demographics with versioned history.'),
    ('view_patient_audit_history', 'View Patient Audit History', 'Medical Records', 'View patient-specific audit and demographic history.'),
    ('view_patient_identifiers', 'View Patient Identifiers', 'Medical Records', 'View authorized patient identifiers.'),
    ('manage_patient_identifiers', 'Manage Patient Identifiers', 'Medical Records', 'Create, amend, deactivate, and select primary patient identifiers.'),
    ('verify_patient_identifiers', 'Verify Patient Identifiers', 'Medical Records', 'Verify patient identifier evidence.'),
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
      'view_medical_record', 'edit_patient_demographics',
      'view_patient_audit_history', 'view_patient_identifiers',
      'manage_patient_identifiers', 'verify_patient_identifiers',
      'view_duplicate_candidates', 'review_duplicate_candidates'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name IN ('Receptionist', 'Doctor', 'Nurse')
  AND p.permission_key IN ('view_medical_record', 'view_patient_identifiers');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name = 'Receptionist'
  AND p.permission_key IN (
      'edit_patient_demographics', 'manage_patient_identifiers',
      'view_duplicate_candidates'
  );

INSERT INTO system_settings (
    setting_key, setting_value, setting_type, setting_group, description,
    default_value, validation_rules, is_public, is_editable, is_system,
    sort_order
) VALUES
    ('mpi.enabled_identifier_types', '["National Identification Number","Insurance Number","Passport Number","External Hospital Number","Legacy Medical Record Number"]', 'array', 'Medical Records', 'Enabled alternate patient identifier types.', '["National Identification Number","Insurance Number","Passport Number","External Hospital Number","Legacy Medical Record Number"]', '{"required":true}', 0, 1, 1, 10),
    ('mpi.global_unique_types', '["National Identification Number","Passport Number"]', 'array', 'Medical Records', 'Identifier types unique across the hospital.', '["National Identification Number","Passport Number"]', '{}', 0, 1, 1, 20),
    ('mpi.authority_unique_types', '["Insurance Number","External Hospital Number","Legacy Medical Record Number"]', 'array', 'Medical Records', 'Identifier types unique within an issuing authority.', '["Insurance Number","External Hospital Number","Legacy Medical Record Number"]', '{}', 0, 1, 1, 30),
    ('mpi.exact_match_threshold', '100', 'integer', 'Medical Records', 'Exact duplicate score threshold.', '100', '{"min":90,"max":100}', 0, 1, 1, 40),
    ('mpi.strong_match_threshold', '80', 'integer', 'Medical Records', 'Strong possible duplicate score threshold.', '80', '{"min":60,"max":99}', 0, 1, 1, 50),
    ('mpi.possible_match_threshold', '55', 'integer', 'Medical Records', 'Possible duplicate score threshold.', '55', '{"min":30,"max":89}', 0, 1, 1, 60),
    ('mpi.search_page_size', '25', 'integer', 'Medical Records', 'Default MPI search page size.', '25', '{"min":10,"max":100}', 0, 1, 1, 70),
    ('mpi.mask_identifier_types', '["National Identification Number","Insurance Number","Passport Number"]', 'array', 'Medical Records', 'Identifier types masked in ordinary displays.', '["National Identification Number","Insurance Number","Passport Number"]', '{}', 0, 1, 1, 80)
ON DUPLICATE KEY UPDATE description = VALUES(description);
