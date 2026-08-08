/*
|--------------------------------------------------------------------------
| Phase 2 - Milestone 2.1 Medical Records Foundation
|--------------------------------------------------------------------------
|
| Adds longitudinal demographic history, patient-aware auditing, protected
| health-information access history, amendment metadata, and the initial
| Medical Records permission catalogue. No later clinical-domain tables are
| introduced by this migration.
|
*/

ALTER TABLE patients
    ADD COLUMN demographic_version INT NOT NULL DEFAULT 1
        AFTER registered_by,
    ADD INDEX idx_patients_demographic_version
        (id, demographic_version);

ALTER TABLE audit_logs
    ADD COLUMN patient_id INT NULL
        AFTER visit_id,
    ADD INDEX idx_audit_patient_created
        (patient_id, created_at),
    ADD CONSTRAINT fk_audit_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL;

CREATE TABLE record_amendments (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    patient_id INT NOT NULL,

    visit_id INT NULL,

    record_type VARCHAR(100) NOT NULL,

    record_id BIGINT NULL,

    proposed_changes LONGTEXT NOT NULL,

    reason TEXT NOT NULL,

    status ENUM(
        'Requested',
        'Approved',
        'Rejected',
        'Applied'
    ) NOT NULL DEFAULT 'Requested',

    requested_by INT NOT NULL,

    reviewed_by INT NULL,

    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    reviewed_at DATETIME NULL,

    applied_at DATETIME NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_record_amendments_patient_status
        (patient_id, status, requested_at),

    INDEX idx_record_amendments_visit
        (visit_id),

    INDEX idx_record_amendments_record
        (record_type, record_id),

    INDEX idx_record_amendments_requested_by
        (requested_by, requested_at),

    CONSTRAINT fk_record_amendments_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_record_amendments_visit
        FOREIGN KEY (visit_id)
        REFERENCES visits(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_record_amendments_requested_by
        FOREIGN KEY (requested_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_record_amendments_reviewed_by
        FOREIGN KEY (reviewed_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_demographic_history (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    patient_id INT NOT NULL,

    amendment_id BIGINT NOT NULL,

    version_no INT NOT NULL,

    previous_values LONGTEXT NOT NULL,

    new_values LONGTEXT NOT NULL,

    changed_fields LONGTEXT NOT NULL,

    reason TEXT NOT NULL,

    changed_by INT NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_patient_demographic_history_version
        UNIQUE (patient_id, version_no),

    INDEX idx_patient_demographic_history_created
        (patient_id, created_at),

    INDEX idx_patient_demographic_history_actor
        (changed_by, created_at),

    INDEX idx_patient_demographic_history_amendment
        (amendment_id),

    CONSTRAINT fk_patient_demographic_history_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_patient_demographic_history_amendment
        FOREIGN KEY (amendment_id)
        REFERENCES record_amendments(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_patient_demographic_history_actor
        FOREIGN KEY (changed_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE record_access_logs (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    patient_id INT NOT NULL,

    visit_id INT NULL,

    user_id INT NOT NULL,

    department_id INT NULL,

    access_type VARCHAR(100) NOT NULL,

    resource_type VARCHAR(100) NOT NULL,

    resource_id BIGINT NULL,

    access_reason VARCHAR(255) NULL,

    ip_address VARCHAR(50) NULL,

    user_agent VARCHAR(255) NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_record_access_patient_created
        (patient_id, created_at),

    INDEX idx_record_access_user_created
        (user_id, created_at),

    INDEX idx_record_access_department_created
        (department_id, created_at),

    INDEX idx_record_access_visit
        (visit_id),

    INDEX idx_record_access_resource
        (resource_type, resource_id, created_at),

    CONSTRAINT fk_record_access_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_record_access_visit
        FOREIGN KEY (visit_id)
        REFERENCES visits(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_record_access_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_record_access_department
        FOREIGN KEY (department_id)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (
    permission_key,
    permission_name,
    module,
    description
)
VALUES
    (
        'view_medical_record',
        'View Medical Records',
        'Medical Records',
        'View an authorized patient longitudinal chart.'
    ),
    (
        'edit_patient_demographics',
        'Edit Patient Demographics',
        'Medical Records',
        'Correct patient demographics with versioned history.'
    ),
    (
        'view_patient_audit_history',
        'View Patient Audit History',
        'Medical Records',
        'View patient-specific audit and demographic history.'
    )
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
    ON p.permission_key = 'view_medical_record'
WHERE r.role_name IN (
    'Receptionist',
    'Records Officer',
    'Doctor',
    'Nurse',
    'Laboratory Scientist',
    'Pharmacist',
    'Physiotherapist',
    'Radiographer',
    'Theatre Staff'
);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
    ON p.permission_key = 'edit_patient_demographics'
WHERE r.role_name IN (
    'Receptionist',
    'Records Officer'
);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
    ON p.permission_key = 'view_patient_audit_history'
WHERE r.role_name = 'Records Officer';
