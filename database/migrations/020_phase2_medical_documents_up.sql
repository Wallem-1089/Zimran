/* Phase 2 Milestone 2.5: secure Medical Documents and immutable file versions. */

CREATE TABLE IF NOT EXISTS medical_documents (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    visit_id INT NULL,
    document_type VARCHAR(80) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    department_id INT NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential','Highly Confidential') NOT NULL DEFAULT 'Standard',
    document_status ENUM('Active','Archived','Entered-in-error') NOT NULL DEFAULT 'Active',
    current_version INT NOT NULL DEFAULT 1,
    uploaded_by INT NOT NULL,
    archived_by INT NULL,
    archived_at DATETIME NULL,
    archive_reason TEXT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_medical_documents_patient_status (patient_id, document_status, created_at),
    INDEX idx_medical_documents_visit_status (visit_id, document_status, created_at),
    INDEX idx_medical_documents_type (document_type, document_status),
    INDEX idx_medical_documents_confidentiality (confidentiality_level, document_status),
    INDEX idx_medical_documents_department (department_id, created_at),
    INDEX idx_medical_documents_uploader (uploaded_by, created_at),
    CONSTRAINT fk_medical_documents_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_documents_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_documents_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_medical_documents_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_documents_archived_by FOREIGN KEY (archived_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS medical_document_versions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT NOT NULL,
    version_number INT NOT NULL,
    storage_provider VARCHAR(40) NOT NULL DEFAULT 'local',
    storage_key VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_extension VARCHAR(20) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    sha256_checksum CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    upload_status ENUM('Pending','Available','Quarantined','Rejected') NOT NULL DEFAULT 'Pending',
    malware_scan_status ENUM('Not Scanned','Clean','Suspicious','Infected','Scan Failed') NOT NULL DEFAULT 'Not Scanned',
    malware_scan_reference VARCHAR(191) NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    replacement_reason TEXT NULL,
    supersedes_version_id BIGINT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_medical_document_version UNIQUE (document_id, version_number),
    CONSTRAINT uq_medical_document_storage_key UNIQUE (storage_key),
    INDEX idx_medical_document_versions_document (document_id, uploaded_at),
    INDEX idx_medical_document_versions_status (upload_status, malware_scan_status),
    INDEX idx_medical_document_versions_uploader (uploaded_by, uploaded_at),
    INDEX idx_medical_document_versions_checksum (sha256_checksum),
    INDEX idx_medical_document_versions_supersedes (supersedes_version_id),
    CONSTRAINT fk_medical_document_versions_document FOREIGN KEY (document_id) REFERENCES medical_documents(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_document_versions_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_document_versions_supersedes FOREIGN KEY (supersedes_version_id) REFERENCES medical_document_versions(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key,permission_name,module,description) VALUES
('view_medical_documents','View Medical Documents','Medical Records','View authorized patient and encounter document metadata.'),
('upload_medical_documents','Upload Medical Documents','Medical Records','Upload authorized patient and encounter documents.'),
('replace_medical_documents','Replace Medical Documents','Medical Records','Create replacement versions of authorized documents.'),
('archive_medical_documents','Archive Medical Documents','Medical Records','Archive, restore, or mark authorized documents entered in error.'),
('download_medical_documents','Download Medical Documents','Medical Records','Download available authorized document versions.'),
('view_confidential_documents','View Confidential Documents','Medical Records','View and download restricted or confidential document details.'),
('view_document_history','View Document History','Medical Records','View authorized immutable document versions.')
ON DUPLICATE KEY UPDATE permission_name=VALUES(permission_name),module=VALUES(module),description=VALUES(description),is_active=1;

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name IN ('Records Officer','Doctor','Nurse','Laboratory Scientist','Pharmacist','Physiotherapist','Radiographer','Theatre Staff','Receptionist','Accountant')
AND p.permission_key IN ('view_medical_documents','upload_medical_documents','download_medical_documents');

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name IN ('Records Officer','Doctor')
AND p.permission_key IN ('replace_medical_documents','view_document_history');

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name='Records Officer'
AND p.permission_key IN ('archive_medical_documents','view_confidential_documents');

INSERT IGNORE INTO role_permissions (role_id,permission_id)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.role_name='Doctor' AND p.permission_key='view_confidential_documents';

INSERT INTO system_settings (setting_key,setting_value,setting_type,setting_group,description,default_value,validation_rules,is_public,is_editable,is_system,sort_order) VALUES
('documents.allowed_types','["referral_letter","identity_document","insurance_document","consent_form","external_laboratory_result","external_radiology_report","discharge_document","clinical_photograph","medical_certificate","correspondence","other"]','array','Medical Records','Enabled Medical Document type keys.','["referral_letter","identity_document","insurance_document","consent_form","external_laboratory_result","external_radiology_report","discharge_document","clinical_photograph","medical_certificate","correspondence","other"]','{"required":true,"schema_values":["referral_letter","identity_document","insurance_document","consent_form","external_laboratory_result","external_radiology_report","discharge_document","clinical_photograph","medical_certificate","correspondence","other"]}',0,1,1,400),
('documents.maximum_upload_bytes','10485760','integer','Medical Records','Maximum accepted Medical Document upload size in bytes.','10485760','{"required":true,"min":1024,"max":41943040}',0,1,1,410),
('documents.allowed_mime_types','["application/pdf","image/jpeg","image/png","text/plain"]','array','Medical Records','Enabled MIME subset within the mandatory server allowlist.','["application/pdf","image/jpeg","image/png","text/plain"]','{"required":true,"schema_values":["application/pdf","image/jpeg","image/png","text/plain"]}',0,1,1,420),
('documents.allowed_extensions','["pdf","jpg","jpeg","png","txt"]','array','Medical Records','Enabled extension subset within the mandatory server allowlist.','["pdf","jpg","jpeg","png","txt"]','{"required":true,"schema_values":["pdf","jpg","jpeg","png","txt"]}',0,1,1,430),
('documents.confidentiality_levels','["Standard","Restricted","Confidential","Highly Confidential"]','array','Medical Records','Enabled document confidentiality classifications.','["Standard","Restricted","Confidential","Highly Confidential"]','{"required":true,"schema_values":["Standard","Restricted","Confidential","Highly Confidential"]}',0,1,1,440),
('documents.default_confidentiality','Standard','string','Medical Records','Default confidentiality for new Medical Documents.','Standard','{"required":true,"allowed_values":["Standard","Restricted","Confidential","Highly Confidential"]}',0,1,1,450),
('documents.malware_scanning_required','false','boolean','Medical Records','Whether unscanned uploads must remain quarantined.','false','{}',0,1,1,460),
('documents.storage_provider','local','string','Medical Records','Permitted active document storage provider.','local','{"required":true,"allowed_values":["local"]}',0,0,1,470),
('documents.download_cache_policy','no-store','string','Medical Records','Cache-Control policy for authorized downloads.','no-store','{"required":true,"allowed_values":["no-store","private, no-cache"]}',0,1,1,480),
('documents.closed_encounter_uploads','false','boolean','Medical Records','Whether closed encounters accept new or replacement attachments.','false','{}',0,1,1,490),
('documents.retention_years','10','integer','Medical Records','Minimum configured retention horizon; no automatic purge is implemented.','10','{"required":true,"min":1,"max":100}',0,1,1,500)
ON DUPLICATE KEY UPDATE description=VALUES(description),default_value=VALUES(default_value),validation_rules=VALUES(validation_rules);
