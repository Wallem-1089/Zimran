CREATE TABLE IF NOT EXISTS billing_discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    invoice_id INT NOT NULL,
    patient_charge_id INT NULL,
    discount_type ENUM('Percentage') NOT NULL DEFAULT 'Percentage',
    discount_value DECIMAL(5,2) NOT NULL,
    discount_amount DECIMAL(12,2) NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Active','Cancelled') NOT NULL DEFAULT 'Active',
    applied_by INT NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_by INT NULL,
    cancelled_at DATETIME NULL,
    cancel_reason TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_billing_discounts_visit (visit_id),
    INDEX idx_billing_discounts_patient (patient_id),
    INDEX idx_billing_discounts_invoice (invoice_id),
    INDEX idx_billing_discounts_charge (patient_charge_id),
    INDEX idx_billing_discounts_status (status),
    CONSTRAINT fk_billing_discounts_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id),
    CONSTRAINT fk_billing_discounts_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id),
    CONSTRAINT fk_billing_discounts_invoice
        FOREIGN KEY (invoice_id) REFERENCES invoices(id),
    CONSTRAINT fk_billing_discounts_charge
        FOREIGN KEY (patient_charge_id) REFERENCES patient_charges(id),
    CONSTRAINT fk_billing_discounts_applied_by
        FOREIGN KEY (applied_by) REFERENCES users(id),
    CONSTRAINT fk_billing_discounts_cancelled_by
        FOREIGN KEY (cancelled_by) REFERENCES users(id),
    CONSTRAINT chk_billing_discounts_allowed_percent
        CHECK (discount_value IN (5.00, 10.00, 15.00)),
    CONSTRAINT chk_billing_discounts_amount
        CHECK (discount_amount >= 0)
);

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_billing_discounts', 'View Billing Discounts', 'Billing', 'View discounts applied to patient invoices.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_billing_discounts');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'apply_billing_discount', 'Apply Billing Discount', 'Billing', 'Apply allowed preset discounts to patient invoices.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'apply_billing_discount');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'cancel_billing_discount', 'Cancel Billing Discount', 'Billing', 'Cancel active patient invoice discounts with a reason.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'cancel_billing_discount');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Super Administrator'
  AND p.permission_key IN (
      'view_billing_discounts',
      'apply_billing_discount',
      'cancel_billing_discount'
  );
