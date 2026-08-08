/* Phase 2 Milestone 2.6: longitudinal and encounter-linked Clinical Notes. */

CREATE TABLE IF NOT EXISTS clinical_notes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    visit_id INT NULL,
    note_type VARCHAR(80) NOT NULL,
    title VARCHAR(200) NOT NULL,
    department_id INT NULL,
    author_id INT NOT NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential','Highly Confidential') NOT NULL DEFAULT 'Standard',
    note_status ENUM('Draft','Signed','Amended','Entered-in-error') NOT NULL DEFAULT 'Draft',
    current_version INT NOT NULL DEFAULT 1,
    signed_by INT NULL,
    signed_at DATETIME NULL,
    locked_at DATETIME NULL,
    amended_at DATETIME NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clinical_notes_patient_status (patient_id, note_status, created_at),
    INDEX idx_clinical_notes_visit_status (visit_id, note_status, created_at),
    INDEX idx_clinical_notes_author_status (author_id, note_status, updated_at),
    INDEX idx_clinical_notes_department (department_id, created_at),
    INDEX idx_clinical_notes_type (note_type, note_status, created_at),
    INDEX idx_clinical_notes_confidentiality (confidentiality_level, note_status),
    CONSTRAINT fk_clinical_notes_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_notes_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_notes_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_clinical_notes_author FOREIGN KEY (author_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_notes_signed_by FOREIGN KEY (signed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clinical_note_versions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    note_id BIGINT NOT NULL,
    version_number INT NOT NULL,
    content LONGTEXT NOT NULL,
    content_format ENUM('Plain Text') NOT NULL DEFAULT 'Plain Text',
    version_status ENUM('Draft','Signed','Amendment Proposal','Amended','Entered-in-error') NOT NULL,
    author_id INT NOT NULL,
    department_id INT NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential','Highly Confidential') NOT NULL DEFAULT 'Standard',
    content_checksum CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    signed_by INT NULL,
    signed_at DATETIME NULL,
    amendment_reason TEXT NULL,
    supersedes_version_id BIGINT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_clinical_note_version UNIQUE (note_id, version_number),
    INDEX idx_clinical_note_versions_note_created (note_id, created_at),
    INDEX idx_clinical_note_versions_author (author_id, created_at),
    INDEX idx_clinical_note_versions_status (version_status, created_at),
    INDEX idx_clinical_note_versions_confidentiality (confidentiality_level, created_at),
    INDEX idx_clinical_note_versions_checksum (content_checksum),
    INDEX idx_clinical_note_versions_supersedes (supersedes_version_id),
    CONSTRAINT fk_clinical_note_versions_note FOREIGN KEY (note_id) REFERENCES clinical_notes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_note_versions_author FOREIGN KEY (author_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_note_versions_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_clinical_note_versions_signed_by FOREIGN KEY (signed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_note_versions_supersedes FOREIGN KEY (supersedes_version_id) REFERENCES clinical_note_versions(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key,permission_name,module,description) VALUES
('view_clinical_notes','View Clinical Notes','Medical Records','View authorized patient-level and encounter-linked Clinical Notes.'),
('create_patient_notes','Create Patient Notes','Medical Records','Create authorized longitudinal patient Clinical Note drafts.'),
('create_encounter_notes','Create Encounter Notes','Medical Records','Create authorized encounter-linked Clinical Note drafts.'),
('edit_own_note_drafts','Edit Own Note Drafts','Medical Records','Append new draft versions to notes authored by the current user.'),
('edit_any_note_draft','Edit Any Note Draft','Medical Records','Append new draft versions to another author\'s authorized draft.'),
('sign_clinical_notes','Sign Clinical Notes','Medical Records','Sign authorized Clinical Note types and lock their content.'),
('amend_signed_notes','Amend Signed Notes','Medical Records','Request or apply authorized amendments to signed Clinical Notes.'),
('approve_note_amendments','Approve Note Amendments','Medical Records','Approve or reject Clinical Note amendment requests.'),
('mark_note_entered_in_error','Mark Note Entered in Error','Medical Records','Mark an authorized Clinical Note entered in error without deleting history.'),
('view_confidential_notes','View Confidential Notes','Medical Records','View restricted or confidential Clinical Note content.'),
('view_note_history','View Clinical Note History','Medical Records','View authorized immutable Clinical Note versions and amendment history.')
ON DUPLICATE KEY UPDATE permission_name=VALUES(permission_name),module=VALUES(module),description=VALUES(description),is_active=1;

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name IN ('Records Officer','Doctor','Nurse')
AND p.permission_key IN ('view_clinical_notes','create_patient_notes','create_encounter_notes','edit_own_note_drafts','view_note_history');

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name='Doctor'
AND p.permission_key IN ('sign_clinical_notes','amend_signed_notes','mark_note_entered_in_error','view_confidential_notes');

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name='Records Officer'
AND p.permission_key IN ('edit_any_note_draft','amend_signed_notes','approve_note_amendments','mark_note_entered_in_error','view_confidential_notes');

INSERT INTO system_settings (setting_key,setting_value,setting_type,setting_group,description,default_value,validation_rules,is_public,is_editable,is_system,sort_order) VALUES
('clinical_notes.enabled_types','["general_clinical_note","medical_records_note","progress_note","care_coordination_note","patient_communication_note","administrative_clinical_note","external_record_summary","other"]','array','Medical Records','Enabled generic Clinical Note type keys.','["general_clinical_note","medical_records_note","progress_note","care_coordination_note","patient_communication_note","administrative_clinical_note","external_record_summary","other"]','{"required":true,"schema_values":["general_clinical_note","medical_records_note","progress_note","care_coordination_note","patient_communication_note","administrative_clinical_note","external_record_summary","other"]}',0,1,1,510),
('clinical_notes.default_type','general_clinical_note','string','Medical Records','Default Clinical Note type.','general_clinical_note','{"required":true,"allowed_values":["general_clinical_note","medical_records_note","progress_note","care_coordination_note","patient_communication_note","administrative_clinical_note","external_record_summary","other"]}',0,1,1,520),
('clinical_notes.maximum_content_length','50000','integer','Medical Records','Maximum plain-text Clinical Note content length.','50000','{"required":true,"min":100,"max":250000}',0,1,1,530),
('clinical_notes.confidentiality_levels','["Standard","Restricted","Confidential","Highly Confidential"]','array','Medical Records','Enabled Clinical Note confidentiality subset.','["Standard","Restricted","Confidential","Highly Confidential"]','{"required":true,"schema_values":["Standard","Restricted","Confidential","Highly Confidential"]}',0,1,1,540),
('clinical_notes.default_confidentiality','Standard','string','Medical Records','Default Clinical Note confidentiality.','Standard','{"required":true,"allowed_values":["Standard","Restricted","Confidential","Highly Confidential"]}',0,1,1,550),
('clinical_notes.allow_self_signing','true','boolean','Medical Records','Allow an authorized clinical author to sign their own draft.','true','{}',0,1,1,560),
('clinical_notes.amendment_approval_required','true','boolean','Medical Records','Require approval before an amendment proposal becomes the current signed record.','true','{}',0,1,1,570),
('clinical_notes.allow_self_amendment_approval','false','boolean','Medical Records','Allow an amendment requester to approve the same request.','false','{}',0,1,1,580),
('clinical_notes.closed_encounter_new_notes','false','boolean','Medical Records','Allow new or edited encounter notes after encounter closure.','false','{}',0,1,1,590),
('clinical_notes.draft_visibility','author_and_authorized_editors','string','Medical Records','Visibility policy for unsigned Clinical Note drafts.','author_and_authorized_editors','{"required":true,"allowed_values":["author_only","author_and_authorized_editors"]}',0,1,1,600),
('clinical_notes.auto_lock_on_signing','true','boolean','Medical Records','Mandatory lock policy for newly signed Clinical Notes.','true','{}',0,0,1,610)
ON DUPLICATE KEY UPDATE description=VALUES(description),default_value=VALUES(default_value),validation_rules=VALUES(validation_rules);
