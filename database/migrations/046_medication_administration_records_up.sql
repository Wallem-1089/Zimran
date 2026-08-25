-- Phase Nursing Drug Chart / Medication Administration Records

CREATE TABLE IF NOT EXISTS medication_administration_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    medication_name VARCHAR(255) NOT NULL,
    scheduled_time DATETIME NOT NULL,
    dose_given VARCHAR(100) NOT NULL,
    route VARCHAR(100) NULL,
    administration_status ENUM('Given','Missed','Refused','Held') NOT NULL DEFAULT 'Given',
    notes TEXT NULL,
    administered_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_mar_prescription (prescription_id),
    KEY idx_mar_visit_time (visit_id, scheduled_time),
    KEY idx_mar_patient_time (patient_id, scheduled_time),
    KEY idx_mar_status_time (administration_status, scheduled_time),
    KEY idx_mar_administered_by_time (administered_by, scheduled_time),
    CONSTRAINT fk_mar_prescription
        FOREIGN KEY (prescription_id) REFERENCES prescriptions(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_mar_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mar_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mar_administered_by
        FOREIGN KEY (administered_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
