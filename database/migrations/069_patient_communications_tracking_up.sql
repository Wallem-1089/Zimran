ALTER TABLE patients
    ADD COLUMN IF NOT EXISTS whatsapp_number VARCHAR(20) NULL AFTER phone;

CREATE TABLE IF NOT EXISTS patient_communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    visit_id INT NULL,
    source_module VARCHAR(80) NOT NULL,
    source_type VARCHAR(80) NOT NULL,
    source_record_id INT NULL,
    document_id BIGINT NULL,
    channel ENUM('WhatsApp') NOT NULL DEFAULT 'WhatsApp',
    recipient_phone VARCHAR(30) NOT NULL,
    message TEXT NOT NULL,
    consent_confirmed TINYINT(1) NOT NULL DEFAULT 0,
    sent_by INT NOT NULL,
    status ENUM('Initiated','Failed') NOT NULL DEFAULT 'Initiated',
    provider_reference VARCHAR(120) NULL,
    error_message TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    CONSTRAINT fk_patient_communications_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_communications_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_patient_communications_document
        FOREIGN KEY (document_id) REFERENCES medical_documents(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_patient_communications_sent_by
        FOREIGN KEY (sent_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_patient_communications_patient (patient_id, created_at),
    INDEX idx_patient_communications_visit (visit_id, created_at),
    INDEX idx_patient_communications_source (source_module, source_type, source_record_id),
    INDEX idx_patient_communications_document (document_id),
    INDEX idx_patient_communications_sent_by (sent_by),
    INDEX idx_patient_communications_status (status, created_at)
);

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT
    'view_patient_communications',
    'View Patient Communications',
    'Patient Communications',
    'View tracked patient communication handoffs such as WhatsApp handoffs.',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM permissions
    WHERE permission_key = 'view_patient_communications'
);

INSERT INTO role_permissions (role_id, permission_id, created_at)
SELECT r.id, p.id, NOW()
FROM roles r
INNER JOIN permissions p
    ON p.permission_key = 'view_patient_communications'
WHERE r.role_name = 'Super Administrator'
  AND NOT EXISTS (
      SELECT 1
      FROM role_permissions rp
      WHERE rp.role_id = r.id
        AND rp.permission_id = p.id
  );
