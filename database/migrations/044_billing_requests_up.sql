SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS billing_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    department_id INT NOT NULL,
    source_module VARCHAR(100) NOT NULL,
    source_record_id INT NULL,
    requested_by INT NOT NULL,
    description TEXT NOT NULL,
    suggested_billable_item_id INT NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    status ENUM('Pending','Charged','Cancelled') NOT NULL DEFAULT 'Pending',
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    patient_charge_id INT NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_billing_requests_visit (visit_id),
    KEY idx_billing_requests_patient (patient_id),
    KEY idx_billing_requests_department (department_id),
    KEY idx_billing_requests_requested_by (requested_by),
    KEY idx_billing_requests_status (status),
    KEY idx_billing_requests_source (source_module, source_record_id),
    KEY idx_billing_requests_suggested_item (suggested_billable_item_id),
    KEY idx_billing_requests_patient_charge (patient_charge_id),
    KEY idx_billing_requests_created_at (created_at),
    CONSTRAINT fk_billing_requests_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_billing_requests_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_billing_requests_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_billing_requests_requested_by
        FOREIGN KEY (requested_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_billing_requests_suggested_item
        FOREIGN KEY (suggested_billable_item_id) REFERENCES billable_items(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_billing_requests_reviewed_by
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_billing_requests_patient_charge
        FOREIGN KEY (patient_charge_id) REFERENCES patient_charges(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_billing_request', 'Create Billing Request', 'Billing', 'Create a non-financial billing recommendation for Accounts review.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_billing_request');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_billing_requests', 'View Billing Requests', 'Billing', 'View department billing recommendations awaiting Accounts review.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_billing_requests');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'review_billing_request', 'Review Billing Request', 'Billing', 'Convert billing recommendations into official patient charges.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'review_billing_request');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'cancel_billing_request', 'Cancel Billing Request', 'Billing', 'Cancel pending billing recommendations without creating charges.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'cancel_billing_request');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN (
    'Doctor',
    'Nurse',
    'Laboratory Scientist',
    'Radiographer',
    'Physiotherapist',
    'Theatre Staff',
    'Pharmacist'
)
  AND p.permission_key = 'create_billing_request';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Accounts', 'Accountant')
  AND p.permission_key IN (
      'view_billing_requests',
      'review_billing_request',
      'cancel_billing_request'
  );

SET FOREIGN_KEY_CHECKS = 1;
