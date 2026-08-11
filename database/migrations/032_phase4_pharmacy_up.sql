SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS prescriptions (
    id INT NOT NULL AUTO_INCREMENT,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    prescribed_by INT NULL,
    department_id INT NULL,
    prescription_source ENUM('Clinical','Direct') NOT NULL,
    inventory_item_id INT NULL,
    medication_name VARCHAR(255) NOT NULL,
    dosage TEXT NULL,
    frequency TEXT NULL,
    duration TEXT NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    instructions TEXT NULL,
    status ENUM('Prescribed','Dispensed','Cancelled') NOT NULL DEFAULT 'Prescribed',
    created_by INT NOT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    dispensed_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_prescriptions_visit_created (visit_id, created_at),
    KEY idx_prescriptions_patient_created (patient_id, created_at),
    KEY idx_prescriptions_status_created (status, created_at),
    KEY idx_prescriptions_source_created (prescription_source, created_at),
    KEY idx_prescriptions_department_created (department_id, created_at),
    KEY idx_prescriptions_item_created (inventory_item_id, created_at),
    KEY idx_prescriptions_prescribed_by_created (prescribed_by, created_at),
    KEY idx_prescriptions_created_by_created (created_by, created_at),
    CONSTRAINT fk_prescriptions_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_prescriptions_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_prescriptions_prescribed_by
        FOREIGN KEY (prescribed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_prescriptions_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_prescriptions_inventory_item
        FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_prescriptions_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_prescriptions_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pharmacy_dispensing (
    id INT NOT NULL AUTO_INCREMENT,
    prescription_id INT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    inventory_item_id INT NOT NULL,
    quantity_dispensed DECIMAL(12,2) NOT NULL,
    dispensing_notes TEXT NULL,
    dispensed_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pharmacy_dispensing_prescription (prescription_id),
    KEY idx_pharmacy_dispensing_visit_created (visit_id, created_at),
    KEY idx_pharmacy_dispensing_patient_created (patient_id, created_at),
    KEY idx_pharmacy_dispensing_item_created (inventory_item_id, created_at),
    KEY idx_pharmacy_dispensing_dispensed_by_created (dispensed_by, created_at),
    CONSTRAINT fk_pharmacy_dispensing_prescription
        FOREIGN KEY (prescription_id) REFERENCES prescriptions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pharmacy_dispensing_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pharmacy_dispensing_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pharmacy_dispensing_inventory_item
        FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pharmacy_dispensing_dispensed_by
        FOREIGN KEY (dispensed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_pharmacy', 'View Pharmacy', 'Pharmacy', 'View prescriptions and dispensing records.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_pharmacy');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_prescription', 'Create Prescription', 'Pharmacy', 'Create pharmacy prescriptions.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_prescription');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'edit_prescription', 'Edit Prescription', 'Pharmacy', 'Edit pharmacy prescriptions before dispensing.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'edit_prescription');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'dispense_prescription', 'Dispense Prescription', 'Pharmacy', 'Dispense pharmacy prescriptions.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'dispense_prescription');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Pharmacist'
  AND p.permission_key IN (
      'view_pharmacy',
      'create_prescription',
      'edit_prescription',
      'dispense_prescription'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key IN (
      'view_pharmacy',
      'create_prescription',
      'edit_prescription'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Nurse'
  AND p.permission_key = 'view_pharmacy';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Records Officer'
  AND p.permission_key = 'view_pharmacy';

SET FOREIGN_KEY_CHECKS = 1;
