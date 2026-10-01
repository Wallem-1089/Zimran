-- Multi-file diagnostic report attachments for Laboratory, Radiology/X-Ray, and ECG.

CREATE TABLE IF NOT EXISTS diagnostic_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_module ENUM('Laboratory','Radiology','ECG') NOT NULL,
    source_record_id INT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_diagnostic_attachments_source (source_module, source_record_id, is_active),
    INDEX idx_diagnostic_attachments_visit (visit_id, uploaded_at),
    INDEX idx_diagnostic_attachments_patient (patient_id, uploaded_at),
    INDEX idx_diagnostic_attachments_uploader (uploaded_by, uploaded_at),
    CONSTRAINT fk_diag_attach_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_diag_attach_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_diag_attach_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
