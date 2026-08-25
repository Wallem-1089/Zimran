-- Patient Stock Usage / Department Consumption.
-- Departments record stock used for a specific patient encounter.

ALTER TABLE stock_transactions
    MODIFY transaction_type ENUM('Receipt','Issue','Return','Adjustment','Consumption') NOT NULL;

CREATE TABLE IF NOT EXISTS patient_stock_usage (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    department_id INT NOT NULL,
    inventory_item_id INT NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    usage_reason TEXT NULL,
    source_module VARCHAR(50) NULL,
    source_record_id INT NULL,
    stock_transaction_id BIGINT NULL,
    billing_request_id INT NULL,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_patient_stock_usage_visit (visit_id),
    KEY idx_patient_stock_usage_patient (patient_id),
    KEY idx_patient_stock_usage_department (department_id),
    KEY idx_patient_stock_usage_item (inventory_item_id),
    KEY idx_patient_stock_usage_transaction (stock_transaction_id),
    KEY idx_patient_stock_usage_billing_request (billing_request_id),
    KEY idx_patient_stock_usage_recorded_by (recorded_by),
    KEY idx_patient_stock_usage_created_at (created_at),
    CONSTRAINT fk_patient_stock_usage_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_stock_usage_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_stock_usage_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_stock_usage_item
        FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_stock_usage_transaction
        FOREIGN KEY (stock_transaction_id) REFERENCES stock_transactions(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_patient_stock_usage_billing_request
        FOREIGN KEY (billing_request_id) REFERENCES billing_requests(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_patient_stock_usage_recorded_by
        FOREIGN KEY (recorded_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_patient_stock_usage', 'View Patient Stock Usage', 'Store', 'View patient-linked department stock usage records.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_patient_stock_usage');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'record_patient_stock_usage', 'Record Patient Stock Usage', 'Store', 'Record department stock used for a patient encounter.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'record_patient_stock_usage');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN (
    'Nurse',
    'Doctor',
    'Laboratory Scientist',
    'Radiographer',
    'Physiotherapist',
    'Theatre Staff'
)
  AND p.permission_key IN ('view_patient_stock_usage', 'record_patient_stock_usage');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Pharmacist', 'Store Officer', 'Accounts', 'Accountant', 'Records Officer')
  AND p.permission_key = 'view_patient_stock_usage';
