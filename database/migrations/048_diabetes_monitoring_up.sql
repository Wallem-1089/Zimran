-- Nursing DM Sheet / Diabetes Monitoring

CREATE TABLE IF NOT EXISTS diabetes_monitoring (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    recorded_at DATETIME NOT NULL,
    blood_glucose DECIMAL(7,2) NOT NULL,
    insulin_given VARCHAR(255) NULL,
    meal_status ENUM('Before Meal','After Meal','Fasting','Random','Bedtime','Not Recorded') NOT NULL DEFAULT 'Not Recorded',
    symptoms TEXT NULL,
    notes TEXT NULL,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_diabetes_monitoring_visit_recorded (visit_id, recorded_at),
    KEY idx_diabetes_monitoring_patient_recorded (patient_id, recorded_at),
    KEY idx_diabetes_monitoring_recorded_by (recorded_by, recorded_at),
    KEY idx_diabetes_monitoring_meal_status (meal_status, recorded_at),
    CONSTRAINT fk_diabetes_monitoring_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_diabetes_monitoring_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_diabetes_monitoring_recorded_by
        FOREIGN KEY (recorded_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
