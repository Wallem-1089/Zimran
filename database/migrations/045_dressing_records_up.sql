SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS dressing_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    wound_site VARCHAR(255) NOT NULL,
    wound_condition TEXT NULL,
    dressing_done TEXT NULL,
    supplies_used TEXT NULL,
    next_dressing_date DATE NULL,
    recorded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_dressing_records_visit (visit_id),
    KEY idx_dressing_records_patient (patient_id),
    KEY idx_dressing_records_recorded_by (recorded_by),
    KEY idx_dressing_records_next_date (next_dressing_date),
    KEY idx_dressing_records_created_at (created_at),
    CONSTRAINT fk_dressing_records_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_dressing_records_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_dressing_records_recorded_by
        FOREIGN KEY (recorded_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
