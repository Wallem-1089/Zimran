CREATE TABLE IF NOT EXISTS patient_charges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    billable_item_id INT NOT NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    source_module VARCHAR(100) NOT NULL,
    source_record_id INT NULL,
    description TEXT NULL,
    status ENUM('Active','Cancelled') NOT NULL DEFAULT 'Active',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_by INT NULL,
    cancelled_at DATETIME NULL,
    UNIQUE KEY uq_patient_charges_source (source_module, source_record_id),
    KEY idx_patient_charges_visit (visit_id),
    KEY idx_patient_charges_patient (patient_id),
    KEY idx_patient_charges_billable_item (billable_item_id),
    KEY idx_patient_charges_status (status),
    KEY idx_patient_charges_created_by (created_by),
    KEY idx_patient_charges_created_at (created_at),
    KEY idx_patient_charges_cancelled_by (cancelled_by),
    CONSTRAINT fk_patient_charges_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_patient_charges_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_patient_charges_billable_item
        FOREIGN KEY (billable_item_id) REFERENCES billable_items(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_patient_charges_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_patient_charges_cancelled_by
        FOREIGN KEY (cancelled_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(40) NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    balance_due DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('Unpaid','Partially Paid','Paid','Cancelled') NOT NULL DEFAULT 'Unpaid',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_invoices_number (invoice_number),
    UNIQUE KEY uq_invoices_visit (visit_id),
    KEY idx_invoices_patient (patient_id),
    KEY idx_invoices_status (status),
    KEY idx_invoices_created_by (created_by),
    KEY idx_invoices_created_at (created_at),
    CONSTRAINT fk_invoices_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('Cash','Card','Transfer','Other') NOT NULL,
    reference TEXT NULL,
    notes TEXT NULL,
    received_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_payments_invoice (invoice_id),
    KEY idx_payments_visit (visit_id),
    KEY idx_payments_patient (patient_id),
    KEY idx_payments_received_by (received_by),
    KEY idx_payments_created_at (created_at),
    CONSTRAINT fk_payments_invoice
        FOREIGN KEY (invoice_id) REFERENCES invoices(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_payments_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_payments_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_payments_received_by
        FOREIGN KEY (received_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'view_billing', 'View Billing', 'Accounts', 'View patient billing, invoices, payments, and receipts.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_billing');

SELECT 'create_patient_charge', 'Create Patient Charge', 'Accounts', 'Create patient charges from billable items.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_patient_charge');

SELECT 'cancel_patient_charge', 'Cancel Patient Charge', 'Accounts', 'Cancel patient charges where allowed.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'cancel_patient_charge');

SELECT 'create_invoice', 'Create Invoice', 'Accounts', 'Create or refresh patient invoices.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_invoice');

SELECT 'record_payment', 'Record Payment', 'Accounts', 'Record patient payments.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'record_payment');

SELECT 'view_receipts', 'View Receipts', 'Accounts', 'View and print payment receipts.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_receipts');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN (
    'Accounts',
    'Accountant',
    'Receptionist',
    'Records Officer',
    'Doctor',
    'Nurse',
    'Laboratory Scientist',
    'Radiographer',
    'Physiotherapist',
    'Theatre Staff',
    'Pharmacist',
    'Store Officer'
)
  AND p.permission_key IN ('view_billing', 'view_receipts');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Accounts', 'Accountant')
  AND p.permission_key IN (
      'create_patient_charge',
      'cancel_patient_charge',
      'create_invoice',
      'record_payment'
  );
