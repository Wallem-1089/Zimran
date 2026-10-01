-- Patient registration billing gate.

ALTER TABLE patients
    MODIFY gender ENUM('Male','Female','Other','Unknown') NULL,
    MODIFY date_of_birth DATE NULL,
    ADD COLUMN registration_status ENUM('PendingPayment','Active','Cancelled') NOT NULL DEFAULT 'Active' AFTER hospital_number,
    ADD COLUMN registration_type ENUM('Normal','Emergency') NOT NULL DEFAULT 'Normal' AFTER registration_status,
    ADD COLUMN registration_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER registration_type,
    ADD COLUMN registration_paid_at DATETIME NULL AFTER registration_fee,
    ADD COLUMN registration_valid_until DATE NULL AFTER registration_paid_at,
    ADD COLUMN registration_completed_at DATETIME NULL AFTER registration_valid_until,
    ADD INDEX idx_patients_registration_status (registration_status),
    ADD INDEX idx_patients_registration_valid_until (registration_valid_until);

UPDATE patients
SET registration_status = 'Active',
    registration_type = COALESCE(NULLIF(registration_type, ''), 'Normal'),
    registration_valid_until = COALESCE(registration_valid_until, DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
    registration_completed_at = COALESCE(registration_completed_at, created_at)
WHERE hospital_number IS NOT NULL;

CREATE TABLE IF NOT EXISTS patient_registration_billing_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    billing_type ENUM('InitialRegistration','MonthlyRenewal') NOT NULL,
    registration_type ENUM('Normal','Emergency') NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('Pending','Paid','Cancelled') NOT NULL DEFAULT 'Pending',
    requested_by INT NOT NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cleared_by INT NULL,
    cleared_at DATETIME NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_prbr_patient_status (patient_id, status),
    KEY idx_prbr_type_status (billing_type, status),
    KEY idx_prbr_requested_by (requested_by),
    KEY idx_prbr_cleared_by (cleared_by),
    CONSTRAINT fk_prbr_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_prbr_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_prbr_cleared_by FOREIGN KEY (cleared_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO billable_items (
    item_code, item_name, item_type, department_id, description, unit_price, unit, is_active, created_by
)
SELECT 'REG-NORMAL', 'Normal Patient Registration', 'Service', d.id, 'Initial normal patient registration fee.', 30000.00, 'Registration', 1, u.id
FROM departments d
INNER JOIN users u ON u.username = 'walter'
WHERE d.department_name = 'Accounts'
  AND NOT EXISTS (SELECT 1 FROM billable_items WHERE item_code = 'REG-NORMAL');

INSERT INTO billable_items (
    item_code, item_name, item_type, department_id, description, unit_price, unit, is_active, created_by
)
SELECT 'REG-EMERGENCY', 'Emergency Patient Registration', 'Service', d.id, 'Initial emergency patient registration fee.', 50000.00, 'Registration', 1, u.id
FROM departments d
INNER JOIN users u ON u.username = 'walter'
WHERE d.department_name = 'Accounts'
  AND NOT EXISTS (SELECT 1 FROM billable_items WHERE item_code = 'REG-EMERGENCY');

INSERT INTO billable_items (
    item_code, item_name, item_type, department_id, description, unit_price, unit, is_active, created_by
)
SELECT 'REG-RENEWAL', 'Monthly Patient Registration Renewal', 'Service', d.id, 'Monthly patient registration renewal fee.', 20000.00, 'Renewal', 1, u.id
FROM departments d
INNER JOIN users u ON u.username = 'walter'
WHERE d.department_name = 'Accounts'
  AND NOT EXISTS (SELECT 1 FROM billable_items WHERE item_code = 'REG-RENEWAL');
