SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS wards (
    id INT NOT NULL AUTO_INCREMENT,
    ward_name VARCHAR(120) NOT NULL,
    ward_code VARCHAR(30) NOT NULL,
    department_id INT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NOT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_wards_code (ward_code),
    UNIQUE KEY uq_wards_name (ward_name),
    KEY idx_wards_department (department_id),
    KEY idx_wards_active (is_active),
    CONSTRAINT fk_wards_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_wards_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_wards_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ward_beds (
    id INT NOT NULL AUTO_INCREMENT,
    ward_id INT NOT NULL,
    bed_label VARCHAR(50) NOT NULL,
    bed_status ENUM('Available','Occupied','Unavailable') NOT NULL DEFAULT 'Available',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NOT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ward_beds_label (ward_id, bed_label),
    KEY idx_ward_beds_status (bed_status),
    KEY idx_ward_beds_active (is_active),
    CONSTRAINT fk_ward_beds_ward
        FOREIGN KEY (ward_id) REFERENCES wards(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ward_beds_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ward_beds_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admissions (
    id INT NOT NULL AUTO_INCREMENT,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    ward_id INT NOT NULL,
    bed_id INT NOT NULL,
    admission_type ENUM('Emergency','Elective','Transfer','Observation') NOT NULL DEFAULT 'Emergency',
    admission_diagnosis TEXT NULL,
    admission_notes TEXT NULL,
    status ENUM('Admitted','Transferred','Discharged','Cancelled') NOT NULL DEFAULT 'Admitted',
    admitted_by INT NOT NULL,
    admitted_at DATETIME NOT NULL,
    discharged_by INT NULL,
    discharged_at DATETIME NULL,
    discharge_destination VARCHAR(120) NULL,
    discharge_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admissions_visit (visit_id),
    KEY idx_admissions_patient (patient_id),
    KEY idx_admissions_ward (ward_id),
    KEY idx_admissions_bed (bed_id),
    KEY idx_admissions_status (status),
    KEY idx_admissions_admitted_at (admitted_at),
    KEY idx_admissions_discharged_at (discharged_at),
    CONSTRAINT fk_admissions_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admissions_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admissions_ward
        FOREIGN KEY (ward_id) REFERENCES wards(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admissions_bed
        FOREIGN KEY (bed_id) REFERENCES ward_beds(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admissions_admitted_by
        FOREIGN KEY (admitted_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admissions_discharged_by
        FOREIGN KEY (discharged_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admission_movements (
    id INT NOT NULL AUTO_INCREMENT,
    admission_id INT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    from_ward_id INT NULL,
    from_bed_id INT NULL,
    to_ward_id INT NULL,
    to_bed_id INT NULL,
    movement_type ENUM('Admission','Transfer','Discharge','Cancel') NOT NULL,
    reason TEXT NULL,
    performed_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_admission_movements_admission (admission_id),
    KEY idx_admission_movements_visit (visit_id),
    KEY idx_admission_movements_patient (patient_id),
    KEY idx_admission_movements_created_at (created_at),
    CONSTRAINT fk_admission_movements_admission
        FOREIGN KEY (admission_id) REFERENCES admissions(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admission_movements_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admission_movements_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admission_movements_from_ward
        FOREIGN KEY (from_ward_id) REFERENCES wards(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_admission_movements_from_bed
        FOREIGN KEY (from_bed_id) REFERENCES ward_beds(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_admission_movements_to_ward
        FOREIGN KEY (to_ward_id) REFERENCES wards(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_admission_movements_to_bed
        FOREIGN KEY (to_bed_id) REFERENCES ward_beds(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_admission_movements_performed_by
        FOREIGN KEY (performed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_admissions', 'View Admissions', 'Admissions', 'View inpatient admissions, ward census, and bed occupancy.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_admissions');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_admission', 'Create Admission', 'Admissions', 'Admit a patient to a ward and bed.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_admission');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'transfer_admission', 'Transfer Admission', 'Admissions', 'Transfer an admitted patient between wards or beds.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'transfer_admission');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'discharge_admission', 'Discharge Admission', 'Admissions', 'Discharge or cancel inpatient admissions.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'discharge_admission');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'manage_wards_beds', 'Manage Wards and Beds', 'Admissions', 'Create and maintain wards and beds.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'manage_wards_beds');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Nurse', 'Records Officer')
  AND p.permission_key IN ('view_admissions', 'create_admission', 'transfer_admission', 'discharge_admission', 'manage_wards_beds');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key IN ('view_admissions', 'create_admission', 'discharge_admission');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Receptionist'
  AND p.permission_key IN ('view_admissions', 'create_admission');

SET FOREIGN_KEY_CHECKS = 1;
