/*
|--------------------------------------------------------------------------
| Hospital Management System
|--------------------------------------------------------------------------
| Database Schema
| Part 1
|
| Tables
|   â€¢ departments
|   â€¢ roles
|   â€¢ users
|
*/

/*
| SAFETY: This baseline is database-neutral. Select and create the intended
| empty database manually before importing it. Automated tooling must use
| database/schema.sql and the guarded CLI database tools.
*/





/*
|--------------------------------------------------------------------------
| Departments
|--------------------------------------------------------------------------
*/

CREATE TABLE departments (

    id INT AUTO_INCREMENT PRIMARY KEY,

    department_name VARCHAR(100) NOT NULL,

    description TEXT NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL
        DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_departments_name
        UNIQUE (department_name)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO departments
(
    department_name,
    description
)
VALUES

('Administrator', 'System administration'),

('Reception', 'Patient reception'),

('Records', 'Medical records'),

('Doctor', 'Medical consultation'),

('Nursing', 'Nursing services'),

('Laboratory', 'Laboratory investigations'),

('Pharmacy', 'Drug dispensing'),

('Physiotherapy', 'Physiotherapy services'),

('X-Ray', 'Radiology and imaging'),

('ECG', 'Electrocardiography services'),

('Theatre', 'Surgical theatre'),

('Accounts', 'Billing and payments'),

('Store', 'Medical store'),

('Orderly', 'Orderly support services');






/*
|--------------------------------------------------------------------------
| Roles
|--------------------------------------------------------------------------
*/

CREATE TABLE roles (

    id INT AUTO_INCREMENT PRIMARY KEY,

    role_name VARCHAR(100) NOT NULL,

    description TEXT NULL,

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL
        DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_roles_name
        UNIQUE (role_name),

    INDEX idx_roles_active (is_active)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles
(
    role_name,
    description
)
VALUES

('System Administrator','Full system access'),

('Receptionist','Patient registration'),

('Records Officer','Medical records'),

('Doctor','Medical consultation'),

('Nurse','Nursing care'),

('Laboratory Scientist','Laboratory investigations'),

('Pharmacist','Medication dispensing'),

('Physiotherapist','Physiotherapy'),

('Radiographer','Radiology'),

('ECG Technician','ECG services'),

('Theatre Staff','Surgical procedures'),

('Accountant','Billing and payments'),

('Store Officer','Medical store'),

('Orderly','Orderly support staff with stock request access');







/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
*/

CREATE TABLE users (

    id INT AUTO_INCREMENT PRIMARY KEY,

    employee_id VARCHAR(30) NOT NULL,

    first_name VARCHAR(100) NOT NULL,

    last_name VARCHAR(100) NOT NULL,

    gender ENUM(
        'Male',
        'Female'
    ) NULL,

    phone VARCHAR(20) NULL,

    email VARCHAR(150) NULL,

    username VARCHAR(50) NOT NULL,

    password VARCHAR(255) NOT NULL,

    department_id INT NOT NULL,

    role_id INT NOT NULL,

    status ENUM(
        'Active',
        'Inactive'
    )
    NOT NULL
    DEFAULT 'Active',

    failed_login_attempts INT
        NOT NULL
        DEFAULT 0,

    last_failed_login DATETIME NULL,

    last_login DATETIME NULL,

    password_changed_at DATETIME NULL,

    must_change_password TINYINT(1)
        NOT NULL
        DEFAULT 0,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL
        DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,



    CONSTRAINT uq_users_employee
        UNIQUE(employee_id),

    CONSTRAINT uq_users_username
        UNIQUE(username),

    INDEX idx_users_department
        (department_id),

    INDEX idx_users_role
        (role_id),

    INDEX idx_users_status
        (status),

    INDEX idx_users_lastname
        (last_name),

    INDEX idx_users_firstname
        (first_name),

    CONSTRAINT fk_users_department

        FOREIGN KEY (department_id)

        REFERENCES departments(id)

        ON UPDATE CASCADE

        ON DELETE RESTRICT,

    CONSTRAINT fk_users_role

        FOREIGN KEY (role_id)

        REFERENCES roles(id)

        ON UPDATE CASCADE

        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

/*
|--------------------------------------------------------------------------
| Permissions
|--------------------------------------------------------------------------
| Fresh baseline imports need these tables before later module permission
| inserts run. Migration 006 remains responsible for the Phase 1 permission
| seed/role grants on existing installations.
*/

CREATE TABLE permissions (

    id INT AUTO_INCREMENT PRIMARY KEY,

    permission_key VARCHAR(100) NOT NULL,

    permission_name VARCHAR(150) NOT NULL,

    module VARCHAR(100) NOT NULL,

    description TEXT NULL,

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_permissions_key UNIQUE (permission_key),

    INDEX idx_permissions_module (module),

    INDEX idx_permissions_active (is_active)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (

    id INT AUTO_INCREMENT PRIMARY KEY,

    role_id INT NOT NULL,

    permission_id INT NOT NULL,

    assigned_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_role_permissions UNIQUE (role_id, permission_id),

    INDEX idx_role_permissions_role (role_id),

    INDEX idx_role_permissions_permission (permission_id),

    CONSTRAINT fk_role_permissions_role
        FOREIGN KEY (role_id)
        REFERENCES roles(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_role_permissions_permission
        FOREIGN KEY (permission_id)
        REFERENCES permissions(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_role_permissions_assigned_by
        FOREIGN KEY (assigned_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_permissions (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    permission_id INT NOT NULL,

    effect ENUM('Allow','Deny') NOT NULL DEFAULT 'Allow',

    assigned_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_user_permissions UNIQUE (user_id, permission_id),

    INDEX idx_user_permissions_user (user_id),

    INDEX idx_user_permissions_permission (permission_id),

    INDEX idx_user_permissions_effect (effect),

    CONSTRAINT fk_user_permissions_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_user_permissions_permission
        FOREIGN KEY (permission_id)
        REFERENCES permissions(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_user_permissions_assigned_by
        FOREIGN KEY (assigned_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
/*
|--------------------------------------------------------------------------
| Patients
|--------------------------------------------------------------------------
*/

CREATE TABLE patients (

    id INT AUTO_INCREMENT PRIMARY KEY,

    hospital_number VARCHAR(30) NULL,

    first_name VARCHAR(100) NOT NULL,

    normalized_first_name VARCHAR(100) NULL,

    middle_name VARCHAR(100) NULL,

    normalized_middle_name VARCHAR(100) NULL,

    last_name VARCHAR(100) NOT NULL,

    normalized_last_name VARCHAR(100) NULL,

    gender ENUM(
        'Male',
        'Female',
        'Other',
        'Unknown'
    ) NOT NULL,

    date_of_birth DATE NOT NULL,

    marital_status VARCHAR(30) NULL,

    occupation VARCHAR(100) NULL,

    place_of_work VARCHAR(150) NULL,

    phone VARCHAR(20) NULL,

    normalized_phone VARCHAR(30) NULL,

    email VARCHAR(150) NULL,

    normalized_email VARCHAR(150) NULL,

    address TEXT NULL,

    state_of_origin VARCHAR(100) NULL,

    nationality VARCHAR(100) NULL,

    ethnic_group VARCHAR(100) NULL,

    religion VARCHAR(100) NULL,

    blood_group VARCHAR(5) NULL,

    genotype VARCHAR(5) NULL,

    allergies TEXT NULL,

    next_of_kin VARCHAR(150) NULL,

    next_of_kin_relationship VARCHAR(100) NULL,

    next_of_kin_phone VARCHAR(20) NULL,

    next_of_kin_address TEXT NULL,

    registered_by INT NULL,

    demographic_version INT NOT NULL DEFAULT 1,

    is_deleted TINYINT(1) NOT NULL DEFAULT 0,

    deleted_at DATETIME NULL,

    deleted_by INT NULL,

    deletion_reason TEXT NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL
        DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_patients_hospital_number
        UNIQUE (hospital_number),

    INDEX idx_patients_last_name
        (last_name),

    INDEX idx_patients_first_name
        (first_name),

    INDEX idx_patients_phone
        (phone),

    INDEX idx_patients_registered_by
        (registered_by),

    INDEX idx_patients_demographic_version
        (id, demographic_version),

    INDEX idx_patients_deleted
        (is_deleted, deleted_at),

    INDEX idx_patients_normalized_name
        (normalized_last_name, normalized_first_name, date_of_birth),

    INDEX idx_patients_normalized_phone
        (normalized_phone),

    INDEX idx_patients_normalized_email
        (normalized_email),

    INDEX idx_patients_dob_normalized_name
        (date_of_birth, normalized_last_name, normalized_first_name),

    CONSTRAINT fk_patients_registered_by

        FOREIGN KEY (registered_by)

        REFERENCES users(id)

        ON UPDATE CASCADE

        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;





/*
|--------------------------------------------------------------------------
| Visits
|--------------------------------------------------------------------------
*/

CREATE TABLE visits (

    id INT AUTO_INCREMENT PRIMARY KEY,

    visit_number VARCHAR(30) NOT NULL,

    patient_id INT NOT NULL,

    visit_date DATETIME NOT NULL,

    visit_type ENUM(
    'Outpatient',
    'Inpatient',
    'Emergency',
    'Referral'
)
NOT NULL
DEFAULT 'Outpatient',

    current_department_id INT NULL,

    attending_doctor_id INT NULL,

    queue_number INT NULL,

    current_department_received_status ENUM(
        'Pending',
        'Received'
    ) NOT NULL DEFAULT 'Pending',

    current_department_received_by INT NULL,

    current_department_received_at DATETIME NULL,

    visit_status ENUM(

        'Waiting',

        'Reception',

        'Records',

        'Nursing',

        'Doctor',

        'Laboratory',

        'X-Ray',

        'ECG',

        'Pharmacy',

        'Physiotherapy',

        'Theatre',

        'Accounts',

        'Store',

        'Completed',

        'Cancelled'

    )
    NOT NULL
    DEFAULT 'Waiting',

    created_by INT NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL
        DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    completed_at DATETIME NULL,

    completed_by INT NULL,

    discharge_diagnosis TEXT NULL,

    discharge_notes TEXT NULL,

    follow_up_instructions TEXT NULL,

    CONSTRAINT uq_visits_number
        UNIQUE (visit_number),

    INDEX idx_visits_patient
        (patient_id),

    INDEX idx_visits_department
        (current_department_id),

    INDEX idx_visits_doctor
        (attending_doctor_id),

    INDEX idx_visits_department_receive
        (current_department_id, current_department_received_status),

    INDEX idx_visits_creator
        (created_by),

    INDEX idx_visits_status
        (visit_status),

    INDEX idx_visits_date
        (visit_date),

    INDEX idx_visits_completed_at
        (completed_at),

    INDEX idx_visits_completed_by
        (completed_by),

    CONSTRAINT fk_visits_patient

        FOREIGN KEY (patient_id)

        REFERENCES patients(id)

        ON UPDATE CASCADE

        ON DELETE RESTRICT,

    CONSTRAINT fk_visits_department

        FOREIGN KEY (current_department_id)

        REFERENCES departments(id)

        ON UPDATE CASCADE

        ON DELETE SET NULL,

    CONSTRAINT fk_visits_doctor

        FOREIGN KEY (attending_doctor_id)

        REFERENCES users(id)

        ON UPDATE CASCADE

        ON DELETE SET NULL,

    CONSTRAINT fk_visits_received_by

        FOREIGN KEY (current_department_received_by)

        REFERENCES users(id)

        ON UPDATE CASCADE

        ON DELETE SET NULL,

    CONSTRAINT fk_visits_created_by

        FOREIGN KEY (created_by)

        REFERENCES users(id)

        ON UPDATE CASCADE

        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

/*
|--------------------------------------------------------------------------
| Encounter Events
|--------------------------------------------------------------------------
*/

CREATE TABLE encounter_events (

    id INT AUTO_INCREMENT PRIMARY KEY,

    visit_id INT NOT NULL,

    event_type VARCHAR(100) NOT NULL,

    event_title VARCHAR(150) NOT NULL,

    event_description TEXT NULL,

    department_id INT NULL,

    performed_by INT NULL,

    event_time DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_encounter_events_visit_time
        (visit_id, event_time, id),

    INDEX idx_encounter_events_department
        (department_id),

    INDEX idx_encounter_events_performed_by
        (performed_by),

    CONSTRAINT fk_encounter_events_visit
        FOREIGN KEY (visit_id)
        REFERENCES visits(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_encounter_events_department
        FOREIGN KEY (department_id)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_encounter_events_performed_by
        FOREIGN KEY (performed_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visit_transfers (

    id INT AUTO_INCREMENT PRIMARY KEY,

    visit_id INT NOT NULL,

    from_department_id INT NULL,

    to_department_id INT NOT NULL,

    from_status VARCHAR(50) NOT NULL,

    to_status VARCHAR(50) NOT NULL,

    transfer_type ENUM(
        'Forward',
        'Return',
        'Referral',
        'Discharge',
        'Completion',
        'Cancellation'
    ) NOT NULL DEFAULT 'Forward',

    previous_status VARCHAR(50) NULL,

    new_status VARCHAR(50) NULL,

    transferred_by INT NOT NULL,

    remarks TEXT NULL,

    transferred_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    received_by INT NULL,

    received_at DATETIME NULL,

    INDEX idx_visit (visit_id),

    INDEX idx_from_department (from_department_id),

    INDEX idx_to_department (to_department_id),

    INDEX idx_transferred_by (transferred_by),

    INDEX idx_transfer_pending
        (visit_id, received_at, transferred_at),

    INDEX idx_transfer_destination_pending
        (to_department_id, received_at, transferred_at),

    CONSTRAINT fk_transfer_visit
        FOREIGN KEY (visit_id)
        REFERENCES visits(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_transfer_from_department
        FOREIGN KEY (from_department_id)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_transfer_to_department
        FOREIGN KEY (to_department_id)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_transfer_user
        FOREIGN KEY (transferred_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

    ,

    CONSTRAINT fk_transfer_received_by

        FOREIGN KEY (received_by)

        REFERENCES users(id)

        ON UPDATE CASCADE

        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


/*
|--------------------------------------------------------------------------
| Audit Logs
|--------------------------------------------------------------------------
*/

CREATE TABLE audit_logs (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    visit_id INT NULL,

    patient_id INT NULL,

    module VARCHAR(100) NOT NULL,

    action VARCHAR(100) NOT NULL,

    description TEXT,

    ip_address VARCHAR(50),

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_audit_user
        (user_id),

    INDEX idx_audit_visit
        (visit_id),

    INDEX idx_audit_patient_created
        (patient_id, created_at),

    INDEX idx_audit_module
        (module),

    INDEX idx_audit_created
        (created_at),

    CONSTRAINT fk_audit_user

        FOREIGN KEY (user_id)

        REFERENCES users(id)

        ON UPDATE CASCADE

        ON DELETE SET NULL,

    CONSTRAINT fk_audit_visit

        FOREIGN KEY (visit_id)

        REFERENCES visits(id)

        ON UPDATE CASCADE

        ON DELETE SET NULL,

    CONSTRAINT fk_audit_patient

        FOREIGN KEY (patient_id)

        REFERENCES patients(id)

        ON UPDATE CASCADE

        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;




/*
|--------------------------------------------------------------------------
| Visit Queue
|--------------------------------------------------------------------------
*/

CREATE TABLE visit_queue (

    id INT AUTO_INCREMENT PRIMARY KEY,

    visit_id INT NOT NULL,

    department_id INT NOT NULL,

    assigned_user_id INT NULL,

    position INT NULL,

    remarks TEXT NULL,

    queue_status ENUM(

        'Waiting',

        'Called',

        'In Progress',

        'Completed',

        'Cancelled'

    )
    NOT NULL
    DEFAULT 'Waiting',

    queued_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    called_at DATETIME NULL,

    started_at DATETIME NULL,

    completed_at DATETIME NULL,

    cancelled_at DATETIME NULL,

    INDEX idx_queue_visit
        (visit_id),

    INDEX idx_queue_department
        (department_id),

    INDEX idx_queue_status
        (queue_status),

    INDEX idx_queue_department_status_position
        (department_id, queue_status, position, queued_at),

    INDEX idx_queue_visit_status
        (visit_id, queue_status),

    INDEX idx_queue_queued_at
        (queued_at),

    INDEX idx_queue_position
        (position),

    CONSTRAINT fk_queue_visit

        FOREIGN KEY (visit_id)

        REFERENCES visits(id)

        ON UPDATE CASCADE

        ON DELETE CASCADE,

    CONSTRAINT fk_queue_department

        FOREIGN KEY (department_id)

        REFERENCES departments(id)

        ON UPDATE CASCADE

        ON DELETE RESTRICT

    ,

    CONSTRAINT fk_queue_assigned_user

        FOREIGN KEY (assigned_user_id)

        REFERENCES users(id)

        ON UPDATE CASCADE

        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


/*
|--------------------------------------------------------------------------
| Medical Records Foundation
|--------------------------------------------------------------------------
*/

CREATE TABLE record_amendments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    visit_id INT NULL,
    record_type VARCHAR(100) NOT NULL,
    record_id BIGINT NULL,
    proposed_changes LONGTEXT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Requested','Approved','Rejected','Applied')
        NOT NULL DEFAULT 'Requested',
    requested_by INT NOT NULL,
    reviewed_by INT NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    applied_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_record_amendments_patient_status
        (patient_id, status, requested_at),
    INDEX idx_record_amendments_visit (visit_id),
    INDEX idx_record_amendments_record (record_type, record_id),
    INDEX idx_record_amendments_requested_by (requested_by, requested_at),
    CONSTRAINT fk_record_amendments_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_record_amendments_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_record_amendments_requested_by
        FOREIGN KEY (requested_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_record_amendments_reviewed_by
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
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
    INDEX idx_patient_demographic_history_amendment (amendment_id),
    CONSTRAINT fk_patient_demographic_history_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_demographic_history_amendment
        FOREIGN KEY (amendment_id) REFERENCES record_amendments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_demographic_history_actor
        FOREIGN KEY (changed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
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
    INDEX idx_record_access_patient_created (patient_id, created_at),
    INDEX idx_record_access_user_created (user_id, created_at),
    INDEX idx_record_access_department_created (department_id, created_at),
    INDEX idx_record_access_visit (visit_id),
    INDEX idx_record_access_resource
        (resource_type, resource_id, created_at),
    CONSTRAINT fk_record_access_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_record_access_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_record_access_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_record_access_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;




/*
|--------------------------------------------------------------------------
| Default Administrator
|--------------------------------------------------------------------------
|
| Generate the password hash with:
|
| echo password_hash('admin123', PASSWORD_DEFAULT);
|
| Replace the value below before importing.
|
*/

INSERT INTO users (

    employee_id,

    first_name,

    last_name,

    gender,

    phone,

    email,

    username,

    password,

    department_id,

    role_id,

    status,

    must_change_password

)

VALUES (

    'EMP000001',

    'System',

    'Administrator',

    'Male',

    NULL,

    'admin@hospital.local',

    'admin',

    '$2y$10$dgHg8.V9d8DJ30tzyBdS/.bolc5DivFkTUFjkeqtEI9rM58L6WoHm',

    1,

    1,

    'Active',

    1

);




/* Phase 2.2 alternate identifier and MPI review structures. */
CREATE TABLE patient_identifiers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    identifier_type VARCHAR(80) NOT NULL,
    identifier_value VARCHAR(255) NOT NULL,
    normalized_value VARCHAR(255) NOT NULL,
    issuing_authority VARCHAR(150) NULL,
    issuing_authority_key VARCHAR(150) NOT NULL DEFAULT '',
    uniqueness_scope ENUM('Global','Authority','Patient','None') NOT NULL DEFAULT 'Patient',
    uniqueness_key VARCHAR(512) NULL,
    issue_date DATE NULL,
    expiry_date DATE NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    primary_key_value VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    verification_status ENUM('Unverified','Verified','Rejected') NOT NULL DEFAULT 'Unverified',
    verified_by INT NULL,
    verified_at DATETIME NULL,
    created_by INT NOT NULL,
    updated_by INT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_patient_identifier_uniqueness (uniqueness_key),
    UNIQUE KEY uq_patient_identifier_primary (primary_key_value),
    INDEX idx_patient_identifiers_patient (patient_id,is_active,identifier_type),
    INDEX idx_patient_identifiers_lookup (identifier_type,normalized_value,is_active),
    INDEX idx_patient_identifiers_authority (identifier_type,issuing_authority_key,normalized_value),
    INDEX idx_patient_identifiers_verification (verification_status,verified_at),
    CONSTRAINT fk_patient_identifiers_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_identifiers_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_identifiers_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_identifiers_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- MILESTONE 2.5 BASELINE: SECURE MEDICAL DOCUMENTS
-- Tables are baseline-represented; Migration 020 remains ledger-applied for seeds.
-- =========================================================

CREATE TABLE medical_documents (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    visit_id INT NULL,
    document_type VARCHAR(80) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    department_id INT NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential','Highly Confidential') NOT NULL DEFAULT 'Standard',
    document_status ENUM('Active','Archived','Entered-in-error') NOT NULL DEFAULT 'Active',
    current_version INT NOT NULL DEFAULT 1,
    uploaded_by INT NOT NULL,
    archived_by INT NULL,
    archived_at DATETIME NULL,
    archive_reason TEXT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_medical_documents_patient_status (patient_id, document_status, created_at),
    INDEX idx_medical_documents_visit_status (visit_id, document_status, created_at),
    INDEX idx_medical_documents_type (document_type, document_status),
    INDEX idx_medical_documents_confidentiality (confidentiality_level, document_status),
    INDEX idx_medical_documents_department (department_id, created_at),
    INDEX idx_medical_documents_uploader (uploaded_by, created_at),
    CONSTRAINT fk_medical_documents_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_documents_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_documents_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_medical_documents_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_documents_archived_by FOREIGN KEY (archived_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medical_document_versions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT NOT NULL,
    version_number INT NOT NULL,
    storage_provider VARCHAR(40) NOT NULL DEFAULT 'local',
    storage_key VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_extension VARCHAR(20) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    sha256_checksum CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    upload_status ENUM('Pending','Available','Quarantined','Rejected') NOT NULL DEFAULT 'Pending',
    malware_scan_status ENUM('Not Scanned','Clean','Suspicious','Infected','Scan Failed') NOT NULL DEFAULT 'Not Scanned',
    malware_scan_reference VARCHAR(191) NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    replacement_reason TEXT NULL,
    supersedes_version_id BIGINT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_medical_document_version UNIQUE (document_id, version_number),
    CONSTRAINT uq_medical_document_storage_key UNIQUE (storage_key),
    INDEX idx_medical_document_versions_document (document_id, uploaded_at),
    INDEX idx_medical_document_versions_status (upload_status, malware_scan_status),
    INDEX idx_medical_document_versions_uploader (uploaded_by, uploaded_at),
    INDEX idx_medical_document_versions_checksum (sha256_checksum),
    INDEX idx_medical_document_versions_supersedes (supersedes_version_id),
    CONSTRAINT fk_medical_document_versions_document FOREIGN KEY (document_id) REFERENCES medical_documents(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_document_versions_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_document_versions_supersedes FOREIGN KEY (supersedes_version_id) REFERENCES medical_document_versions(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE patient_identifier_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    identifier_id BIGINT NOT NULL,
    patient_id INT NOT NULL,
    version_no INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    previous_snapshot LONGTEXT NULL,
    new_snapshot LONGTEXT NOT NULL,
    reason TEXT NOT NULL,
    changed_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_patient_identifier_history_version (identifier_id,version_no),
    INDEX idx_identifier_history_patient (patient_id,created_at),
    INDEX idx_identifier_history_actor (changed_by,created_at),
    CONSTRAINT fk_identifier_history_identifier FOREIGN KEY (identifier_id) REFERENCES patient_identifiers(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_identifier_history_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_identifier_history_actor FOREIGN KEY (changed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_duplicate_candidates (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id_low INT NOT NULL,
    patient_id_high INT NOT NULL,
    match_score DECIMAL(5,2) NOT NULL,
    classification ENUM('Exact Match','Strong Possible Match','Possible Match','Low Confidence') NOT NULL,
    matched_factors LONGTEXT NOT NULL,
    status ENUM('Pending','Confirmed Duplicate','Not Duplicate','Deferred','Merge Requested') NOT NULL DEFAULT 'Pending',
    review_decision VARCHAR(100) NULL,
    review_reason TEXT NULL,
    detected_by INT NULL,
    detected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_duplicate_candidate_pair (patient_id_low,patient_id_high),
    CHECK (patient_id_low < patient_id_high),
    INDEX idx_duplicate_candidates_status (status,classification,detected_at),
    INDEX idx_duplicate_candidates_low (patient_id_low,status),
    INDEX idx_duplicate_candidates_high (patient_id_high,status),
    CONSTRAINT fk_duplicate_candidates_low FOREIGN KEY (patient_id_low) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_duplicate_candidates_high FOREIGN KEY (patient_id_high) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_duplicate_candidates_detected_by FOREIGN KEY (detected_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_duplicate_candidates_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*
|--------------------------------------------------------------------------
| Useful Sample Queries
|--------------------------------------------------------------------------
*/

/*

-- View all users

SELECT
    u.employee_id,
    u.first_name,
    u.last_name,
    d.department_name,
    r.role_name
FROM users u
INNER JOIN departments d
    ON d.id = u.department_id
INNER JOIN roles r
    ON r.id = u.role_id;



-- View all patients

SELECT *
FROM patients
ORDER BY last_name;



-- View patient visits

SELECT
    v.visit_number,
    p.hospital_number,
    p.first_name,
    p.last_name,
    v.visit_status
FROM visits v
INNER JOIN patients p
    ON p.id = v.patient_id;



*/

-- =========================================================
-- MILESTONE 2.4 BASELINE: PROBLEM LIST AND MEDICAL HISTORY
-- Tables are baseline-represented; Migration 019 remains ledger-applied for seeds.
-- =========================================================
/* Phase 2 Milestone 2.4: longitudinal Problem List and structured history. */

CREATE TABLE patient_problems (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    source_visit_id INT NULL,
    problem_code_system VARCHAR(80) NULL,
    problem_code VARCHAR(80) NULL,
    problem_name VARCHAR(200) NOT NULL,
    normalized_problem_name VARCHAR(200) NOT NULL,
    category ENUM('Chronic Condition','Acute Problem','Historical Diagnosis','Surgical Condition','Risk Factor','Other') NOT NULL DEFAULT 'Other',
    clinical_status ENUM('Active','Inactive','Resolved','Entered-in-error') NOT NULL DEFAULT 'Active',
    verification_status ENUM('Unverified','Confirmed','Refuted') NOT NULL DEFAULT 'Unverified',
    severity ENUM('Mild','Moderate','Severe','Unknown') NOT NULL DEFAULT 'Unknown',
    confidentiality_level ENUM('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
    onset_date DATE NULL,
    recorded_date DATE NOT NULL,
    resolved_date DATE NULL,
    active_problem_key VARCHAR(512) NULL,
    recorded_by INT NOT NULL,
    verified_by INT NULL,
    verified_at DATETIME NULL,
    resolved_by INT NULL,
    notes TEXT NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_patient_problem_active UNIQUE (active_problem_key),
    INDEX idx_patient_problems_status (patient_id, clinical_status, verification_status),
    INDEX idx_patient_problems_severity (patient_id, clinical_status, severity),
    INDEX idx_patient_problems_name (normalized_problem_name, clinical_status),
    INDEX idx_patient_problems_code (problem_code_system, problem_code),
    INDEX idx_patient_problems_visit (source_visit_id),
    INDEX idx_patient_problems_confidentiality (confidentiality_level, clinical_status),
    CONSTRAINT fk_patient_problems_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_problems_visit FOREIGN KEY (source_visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_problems_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_problems_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_problems_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_problem_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    problem_id BIGINT NOT NULL,
    patient_id INT NOT NULL,
    version_no INT NOT NULL,
    action VARCHAR(60) NOT NULL,
    previous_snapshot LONGTEXT NULL,
    new_snapshot LONGTEXT NOT NULL,
    reason TEXT NOT NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
    changed_by INT NOT NULL,
    department_id INT NULL,
    visit_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_patient_problem_history_version UNIQUE (problem_id, version_no),
    INDEX idx_problem_history_patient (patient_id, created_at),
    INDEX idx_problem_history_actor (changed_by, created_at),
    INDEX idx_problem_history_visit (visit_id, created_at),
    INDEX idx_problem_history_confidentiality (confidentiality_level, created_at),
    CONSTRAINT fk_problem_history_problem FOREIGN KEY (problem_id) REFERENCES patient_problems(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_problem_history_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_problem_history_actor FOREIGN KEY (changed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_problem_history_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_problem_history_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_medical_history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    source_visit_id INT NULL,
    history_type ENUM('Past Medical History','Surgical History','Family History','Social History','Obstetric History','Immunization History','Previous Hospitalization','Previous Procedure','Other') NOT NULL,
    title VARCHAR(200) NOT NULL,
    normalized_title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NULL,
    date_precision ENUM('Exact','Month','Year','Unknown') NOT NULL DEFAULT 'Unknown',
    status ENUM('Active','Historical','Entered-in-error') NOT NULL DEFAULT 'Historical',
    source VARCHAR(150) NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
    recorded_by INT NOT NULL,
    verified_by INT NULL,
    verified_at DATETIME NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_medical_history_patient_type (patient_id, history_type, status),
    INDEX idx_medical_history_title (normalized_title, history_type),
    INDEX idx_medical_history_event (patient_id, event_date),
    INDEX idx_medical_history_visit (source_visit_id),
    INDEX idx_medical_history_confidentiality (confidentiality_level, status),
    CONSTRAINT fk_medical_history_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_visit FOREIGN KEY (source_visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_medical_history_versions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    history_entry_id BIGINT NOT NULL,
    patient_id INT NOT NULL,
    version_no INT NOT NULL,
    action VARCHAR(60) NOT NULL,
    previous_snapshot LONGTEXT NULL,
    new_snapshot LONGTEXT NOT NULL,
    reason TEXT NOT NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential') NOT NULL DEFAULT 'Standard',
    changed_by INT NOT NULL,
    department_id INT NULL,
    visit_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_medical_history_version UNIQUE (history_entry_id, version_no),
    INDEX idx_medical_history_versions_patient (patient_id, created_at),
    INDEX idx_medical_history_versions_actor (changed_by, created_at),
    INDEX idx_medical_history_versions_visit (visit_id, created_at),
    INDEX idx_medical_history_versions_confidentiality (confidentiality_level, created_at),
    CONSTRAINT fk_medical_history_versions_entry FOREIGN KEY (history_entry_id) REFERENCES patient_medical_history(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_versions_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_versions_actor FOREIGN KEY (changed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_medical_history_versions_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_medical_history_versions_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- MILESTONE 2.6 BASELINE: CLINICAL NOTES
-- Tables are baseline-represented; Migration 021 remains ledger-applied for seeds.
-- =========================================================

CREATE TABLE clinical_notes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    visit_id INT NULL,
    note_type VARCHAR(80) NOT NULL,
    title VARCHAR(200) NOT NULL,
    department_id INT NULL,
    author_id INT NOT NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential','Highly Confidential') NOT NULL DEFAULT 'Standard',
    note_status ENUM('Draft','Signed','Amended','Entered-in-error') NOT NULL DEFAULT 'Draft',
    current_version INT NOT NULL DEFAULT 1,
    signed_by INT NULL,
    signed_at DATETIME NULL,
    locked_at DATETIME NULL,
    amended_at DATETIME NULL,
    version INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_clinical_notes_patient_status (patient_id,note_status,created_at),
    INDEX idx_clinical_notes_visit_status (visit_id,note_status,created_at),
    INDEX idx_clinical_notes_author_status (author_id,note_status,updated_at),
    INDEX idx_clinical_notes_department (department_id,created_at),
    INDEX idx_clinical_notes_type (note_type,note_status,created_at),
    INDEX idx_clinical_notes_confidentiality (confidentiality_level,note_status),
    CONSTRAINT fk_clinical_notes_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_notes_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_notes_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_clinical_notes_author FOREIGN KEY (author_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_notes_signed_by FOREIGN KEY (signed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clinical_note_versions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    note_id BIGINT NOT NULL,
    version_number INT NOT NULL,
    content LONGTEXT NOT NULL,
    content_format ENUM('Plain Text') NOT NULL DEFAULT 'Plain Text',
    version_status ENUM('Draft','Signed','Amendment Proposal','Amended','Entered-in-error') NOT NULL,
    author_id INT NOT NULL,
    department_id INT NULL,
    confidentiality_level ENUM('Standard','Restricted','Confidential','Highly Confidential') NOT NULL DEFAULT 'Standard',
    content_checksum CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    signed_by INT NULL,
    signed_at DATETIME NULL,
    amendment_reason TEXT NULL,
    supersedes_version_id BIGINT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_clinical_note_version UNIQUE (note_id,version_number),
    INDEX idx_clinical_note_versions_note_created (note_id,created_at),
    INDEX idx_clinical_note_versions_author (author_id,created_at),
    INDEX idx_clinical_note_versions_status (version_status,created_at),
    INDEX idx_clinical_note_versions_confidentiality (confidentiality_level,created_at),
    INDEX idx_clinical_note_versions_checksum (content_checksum),
    INDEX idx_clinical_note_versions_supersedes (supersedes_version_id),
    CONSTRAINT fk_clinical_note_versions_note FOREIGN KEY (note_id) REFERENCES clinical_notes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_note_versions_author FOREIGN KEY (author_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_note_versions_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_clinical_note_versions_signed_by FOREIGN KEY (signed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clinical_note_versions_supersedes FOREIGN KEY (supersedes_version_id) REFERENCES clinical_note_versions(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- PHASE 3.1 BASELINE: CONSULTATION + DEPARTMENT NOTIFICATIONS
-- Tables are baseline-represented; Migration 022 remains ledger-applied for existing installs.
-- =========================================================

CREATE TABLE consultations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    department_id INT NULL,
    presenting_complaint TEXT NOT NULL,
    history_of_presenting_complaint TEXT NOT NULL,
    examination_findings TEXT NOT NULL,
    assessment TEXT NOT NULL,
    diagnosis TEXT NOT NULL,
    treatment_plan TEXT NOT NULL,
    advice TEXT NULL,
    follow_up TEXT NULL,
    referral_notes TEXT NULL,
    status ENUM('Draft','Completed') NOT NULL DEFAULT 'Draft',
    created_by INT NOT NULL,
    updated_by INT NULL,
    completed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_consultations_visit (visit_id),
    INDEX idx_consultations_patient_status (patient_id,status,created_at),
    INDEX idx_consultations_doctor_status (doctor_id,status,created_at),
    INDEX idx_consultations_department (department_id,created_at),
    CONSTRAINT fk_consultations_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_consultations_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE department_notifications (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    from_department_id INT NULL,
    to_department_id INT NOT NULL,
    sent_by INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('Unread','Read','Resolved') NOT NULL DEFAULT 'Unread',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_by INT NULL,
    read_at DATETIME NULL,
    resolved_by INT NULL,
    resolved_at DATETIME NULL,
    INDEX idx_department_notifications_to_status (to_department_id,status,created_at),
    INDEX idx_department_notifications_visit (visit_id,created_at),
    INDEX idx_department_notifications_patient (patient_id,created_at),
    INDEX idx_department_notifications_sender (sent_by,created_at),
    CONSTRAINT fk_department_notifications_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_department_notifications_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_department_notifications_from_department FOREIGN KEY (from_department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_department_notifications_to_department FOREIGN KEY (to_department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_department_notifications_sent_by FOREIGN KEY (sent_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_department_notifications_read_by FOREIGN KEY (read_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_department_notifications_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- PHASE 3.2 BASELINE: VITAL SIGNS
-- Tables are baseline-represented; Migration 023 remains ledger-applied for existing installs.
-- =========================================================

CREATE TABLE vital_signs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    department_id INT NULL,
    recorded_by INT NOT NULL,
    temperature DECIMAL(5,2) NULL,
    pulse INT NULL,
    respiratory_rate INT NULL,
    systolic_bp INT NULL,
    diastolic_bp INT NULL,
    oxygen_saturation DECIMAL(5,2) NULL,
    weight DECIMAL(6,2) NULL,
    height DECIMAL(6,2) NULL,
    bmi DECIMAL(6,2) NULL,
    blood_glucose DECIMAL(7,2) NULL,
    pain_score TINYINT NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vital_signs_visit_created (visit_id, created_at),
    INDEX idx_vital_signs_patient_created (patient_id, created_at),
    INDEX idx_vital_signs_recorded_by_created (recorded_by, created_at),
    INDEX idx_vital_signs_department_created (department_id, created_at),
    CONSTRAINT fk_vital_signs_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_vital_signs_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_vital_signs_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_vital_signs_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- PHASE 3.3 BASELINE: NURSING
-- Tables are baseline-represented; Migration 024 remains ledger-applied for existing installs.
-- =========================================================

CREATE TABLE nursing_assessments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    nurse_id INT NULL,
    department_id INT NULL,
    general_condition TEXT NULL,
    nursing_observation TEXT NULL,
    pain_assessment TEXT NULL,
    mobility TEXT NULL,
    nutrition TEXT NULL,
    elimination TEXT NULL,
    skin_assessment TEXT NULL,
    fall_risk TEXT NULL,
    nursing_interventions TEXT NULL,
    patient_response TEXT NULL,
    handover_notes TEXT NULL,
    additional_notes TEXT NULL,
    status ENUM('Draft','Completed') NOT NULL DEFAULT 'Draft',
    created_by INT NOT NULL,
    updated_by INT NULL,
    completed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_nursing_assessments_visit (visit_id),
    INDEX idx_nursing_assessments_patient_created (patient_id, created_at),
    INDEX idx_nursing_assessments_nurse_created (nurse_id, created_at),
    INDEX idx_nursing_assessments_department_created (department_id, created_at),
    INDEX idx_nursing_assessments_status_created (status, created_at),
    CONSTRAINT fk_nursing_assessments_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_nursing_assessments_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_nursing_assessments_nurse FOREIGN KEY (nurse_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_nursing_assessments_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_nursing_assessments_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_nursing_assessments_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_nursing_assessments_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dressing_records (
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
    CONSTRAINT fk_dressing_records_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_dressing_records_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_dressing_records_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- PHASE 3.4 BASELINE: LABORATORY
-- Tables are baseline-represented; Migration 025 remains ledger-applied for existing installs.
-- =========================================================

CREATE TABLE laboratory_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    requested_by INT NOT NULL,
    department_id INT NULL,
    request_source ENUM('Clinical','Direct') NOT NULL DEFAULT 'Clinical',
    tests_requested TEXT NOT NULL,
    clinical_information TEXT NULL,
    priority ENUM('Routine','Urgent') NOT NULL DEFAULT 'Routine',
    status ENUM('Requested','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_laboratory_requests_visit_created (visit_id, created_at),
    INDEX idx_laboratory_requests_patient_created (patient_id, created_at),
    INDEX idx_laboratory_requests_department_created (department_id, created_at),
    INDEX idx_laboratory_requests_status_created (status, created_at),
    INDEX idx_laboratory_requests_source_created (request_source, created_at),
    INDEX idx_laboratory_requests_requested_by_created (requested_by, created_at),
    CONSTRAINT fk_laboratory_requests_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_requests_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_requests_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_requests_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE laboratory_results (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    laboratory_request_id BIGINT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    sample_taken TEXT NULL,
    findings TEXT NULL,
    result TEXT NOT NULL,
    interpretation TEXT NULL,
    performed_by INT NOT NULL,
    completed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_laboratory_results_request (laboratory_request_id),
    INDEX idx_laboratory_results_visit_created (visit_id, created_at),
    INDEX idx_laboratory_results_patient_created (patient_id, created_at),
    INDEX idx_laboratory_results_performed_by_created (performed_by, created_at),
    INDEX idx_laboratory_results_completed_at (completed_at),
    CONSTRAINT fk_laboratory_results_request FOREIGN KEY (laboratory_request_id) REFERENCES laboratory_requests(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_results_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_results_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_results_performed_by FOREIGN KEY (performed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_laboratory_results_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description) VALUES
('view_laboratory', 'View Laboratory', 'Laboratory', 'View laboratory requests and results.'),
('create_laboratory_request', 'Create Laboratory Request', 'Laboratory', 'Create a laboratory request for an encounter.'),
('process_laboratory_request', 'Process Laboratory Request', 'Laboratory', 'Start and process laboratory requests.'),
('enter_laboratory_result', 'Enter Laboratory Result', 'Laboratory', 'Enter a laboratory result.'),
('edit_laboratory_result', 'Edit Laboratory Result', 'Laboratory', 'Edit a laboratory result.'),
('complete_laboratory_request', 'Complete Laboratory Request', 'Laboratory', 'Complete a laboratory request.')
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Laboratory Scientist'
  AND p.permission_key IN (
      'view_laboratory',
      'create_laboratory_request',
      'process_laboratory_request',
      'enter_laboratory_result',
      'edit_laboratory_result',
      'complete_laboratory_request'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key IN (
      'view_laboratory',
      'create_laboratory_request'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Nurse'
  AND p.permission_key = 'view_laboratory';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Records Officer'
  AND p.permission_key = 'view_laboratory';

-- =========================================================
-- PHASE 3.5 BASELINE: RADIOLOGY
-- Tables are baseline-represented; Migration 027 remains ledger-applied for existing installs.
-- =========================================================

CREATE TABLE radiology_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    requested_by INT NOT NULL,
    department_id INT NULL,
    request_source ENUM('Clinical','Direct') NOT NULL DEFAULT 'Clinical',
    study_requested TEXT NOT NULL,
    clinical_indication TEXT NULL,
    priority ENUM('Routine','Urgent') NOT NULL DEFAULT 'Routine',
    status ENUM('Requested','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_radiology_requests_visit_created (visit_id, created_at),
    INDEX idx_radiology_requests_patient_created (patient_id, created_at),
    INDEX idx_radiology_requests_department_created (department_id, created_at),
    INDEX idx_radiology_requests_status_created (status, created_at),
    INDEX idx_radiology_requests_source_created (request_source, created_at),
    INDEX idx_radiology_requests_requested_by_created (requested_by, created_at),
    CONSTRAINT fk_radiology_requests_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_requests_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_requests_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_requests_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE radiology_reports (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    radiology_request_id BIGINT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    findings TEXT NULL,
    impression TEXT NOT NULL,
    recommendation TEXT NULL,
    performed_by INT NOT NULL,
    completed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_radiology_reports_request (radiology_request_id),
    INDEX idx_radiology_reports_visit_created (visit_id, created_at),
    INDEX idx_radiology_reports_patient_created (patient_id, created_at),
    INDEX idx_radiology_reports_performed_by_created (performed_by, created_at),
    INDEX idx_radiology_reports_completed_at (completed_at),
    CONSTRAINT fk_radiology_reports_request FOREIGN KEY (radiology_request_id) REFERENCES radiology_requests(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_reports_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_reports_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_reports_performed_by FOREIGN KEY (performed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_radiology_reports_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description) VALUES
('view_radiology', 'View Radiology', 'Radiology', 'View radiology requests and reports.'),
('create_radiology_request', 'Create Radiology Request', 'Radiology', 'Create a radiology request for an encounter.'),
('process_radiology_request', 'Process Radiology Request', 'Radiology', 'Start and process radiology requests.'),
('enter_radiology_report', 'Enter Radiology Report', 'Radiology', 'Enter a radiology report.'),
('edit_radiology_report', 'Edit Radiology Report', 'Radiology', 'Edit a radiology report.'),
('complete_radiology_request', 'Complete Radiology Request', 'Radiology', 'Complete a radiology request.')
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Radiographer'
  AND p.permission_key IN (
      'view_radiology',
      'create_radiology_request',
      'process_radiology_request',
      'enter_radiology_report',
      'edit_radiology_report',
      'complete_radiology_request'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key IN (
      'view_radiology',
      'create_radiology_request'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Nurse'
  AND p.permission_key = 'view_radiology';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Records Officer'
  AND p.permission_key = 'view_radiology';

-- =========================================================
-- PHASE 5 ECG BASELINE
-- Tables are baseline-represented; Migration 058 remains ledger-applied for existing installs.
-- =========================================================

CREATE TABLE ecg_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    requested_by INT NOT NULL,
    department_id INT NULL,
    request_source ENUM('Clinical','Direct') NOT NULL DEFAULT 'Clinical',
    study_requested VARCHAR(255) NOT NULL DEFAULT 'ECG',
    clinical_indication TEXT NULL,
    priority ENUM('Routine','Urgent') NOT NULL DEFAULT 'Routine',
    status ENUM('Requested','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_ecg_requests_visit_created (visit_id, created_at),
    INDEX idx_ecg_requests_patient_created (patient_id, created_at),
    INDEX idx_ecg_requests_status_created (status, created_at),
    INDEX idx_ecg_requests_department_status (department_id, status),
    INDEX idx_ecg_requests_requested_by (requested_by),
    CONSTRAINT fk_ecg_requests_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_requests_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_requests_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_requests_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ecg_reports (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ecg_request_id BIGINT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    chart_original_name VARCHAR(255) NULL,
    chart_stored_path VARCHAR(500) NULL,
    chart_mime_type VARCHAR(120) NULL,
    chart_file_size BIGINT NULL,
    notes TEXT NULL,
    remarks TEXT NULL,
    performed_by INT NOT NULL,
    updated_by INT NULL,
    completed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_ecg_reports_request (ecg_request_id),
    INDEX idx_ecg_reports_visit_created (visit_id, created_at),
    INDEX idx_ecg_reports_patient_created (patient_id, created_at),
    INDEX idx_ecg_reports_performed_by (performed_by),
    CONSTRAINT fk_ecg_reports_request FOREIGN KEY (ecg_request_id) REFERENCES ecg_requests(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_reports_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_reports_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_reports_performed_by FOREIGN KEY (performed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ecg_reports_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ecg_reports_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active) VALUES
('view_ecg', 'View ECG', 'ECG', 'View ECG requests, scanned charts, notes, and remarks.', 1),
('create_ecg_request', 'Create ECG Request', 'ECG', 'Create a clinical or direct ECG request.', 1),
('process_ecg_request', 'Process ECG Request', 'ECG', 'Start and process ECG requests.', 1),
('upload_ecg_chart', 'Upload ECG Chart', 'ECG', 'Upload scanned ECG charts and add notes.', 1),
('edit_ecg_report', 'Edit ECG Report', 'ECG', 'Edit ECG chart notes and remarks before completion.', 1),
('complete_ecg_request', 'Complete ECG Request', 'ECG', 'Complete ECG requests after chart upload/reporting.', 1)
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description),
    is_active = 1;

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'ECG Technician'
  AND p.permission_key IN (
      'view_ecg',
      'create_ecg_request',
      'process_ecg_request',
      'upload_ecg_chart',
      'edit_ecg_report',
      'complete_ecg_request',
      'view_medical_record'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key IN ('view_ecg', 'create_ecg_request');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Nurse', 'Records Officer', 'Laboratory Scientist', 'Radiographer', 'Physiotherapist', 'Theatre Staff', 'Pharmacist')
  AND p.permission_key = 'view_ecg';

/*
|--------------------------------------------------------------------------  
| Phase 4.1 Accounts / Price Catalogue
|--------------------------------------------------------------------------  
*/

CREATE TABLE billable_items (
    id INT NOT NULL AUTO_INCREMENT,
    item_code VARCHAR(30) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_type ENUM('Service','Product') NOT NULL,
    department_id INT NULL,
    description TEXT NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    unit VARCHAR(50) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NOT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_billable_items_code (item_code),
    KEY idx_billable_items_name (item_name),
    KEY idx_billable_items_type (item_type),
    KEY idx_billable_items_department (department_id),
    KEY idx_billable_items_status (is_active),
    KEY idx_billable_items_created_at (created_at),
    KEY idx_billable_items_created_by (created_by),
    KEY idx_billable_items_updated_by (updated_by),
    CONSTRAINT fk_billable_items_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_billable_items_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_billable_items_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_billable_items', 'View Billable Items', 'Accounts', 'View the price catalogue.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_billable_items');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_billable_items', 'Create Billable Items', 'Accounts', 'Create price catalogue items.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_billable_items');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'edit_billable_items', 'Edit Billable Items', 'Accounts', 'Edit price catalogue items.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'edit_billable_items');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'manage_billable_item_status', 'Manage Billable Item Status', 'Accounts', 'Activate and deactivate price catalogue items.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'manage_billable_item_status');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Accountant'
  AND p.permission_key IN (
      'view_billable_items',
      'create_billable_items',
      'edit_billable_items',
      'manage_billable_item_status'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Doctor','Nurse','Laboratory Scientist','Radiographer','Physiotherapist','Theatre Staff','Pharmacist','Receptionist','Records Officer','Store Officer')
  AND p.permission_key = 'view_billable_items';

/*
|--------------------------------------------------------------------------  
| Phase 4.4 Billing / Patient Accounts
|--------------------------------------------------------------------------  
*/

CREATE TABLE patient_charges (
    id INT NOT NULL AUTO_INCREMENT,
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
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_by INT NULL,
    cancelled_at DATETIME NULL,
    PRIMARY KEY (id),
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
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_charges_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_charges_billable_item
        FOREIGN KEY (billable_item_id) REFERENCES billable_items(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_charges_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_patient_charges_cancelled_by
        FOREIGN KEY (cancelled_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
    id INT NOT NULL AUTO_INCREMENT,
    invoice_number VARCHAR(40) NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    balance_due DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('Unpaid','Partially Paid','Paid','Cancelled') NOT NULL DEFAULT 'Unpaid',
    created_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_invoices_number (invoice_number),
    UNIQUE KEY uq_invoices_visit (visit_id),
    KEY idx_invoices_patient (patient_id),
    KEY idx_invoices_status (status),
    KEY idx_invoices_created_by (created_by),
    KEY idx_invoices_created_at (created_at),
    CONSTRAINT fk_invoices_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_invoices_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_invoices_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id INT NOT NULL AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('Cash','Card','Transfer','Other') NOT NULL,
    reference TEXT NULL,
    notes TEXT NULL,
    received_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payments_invoice (invoice_id),
    KEY idx_payments_visit (visit_id),
    KEY idx_payments_patient (patient_id),
    KEY idx_payments_received_by (received_by),
    KEY idx_payments_created_at (created_at),
    CONSTRAINT fk_payments_invoice
        FOREIGN KEY (invoice_id) REFERENCES invoices(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payments_visit
        FOREIGN KEY (visit_id) REFERENCES visits(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payments_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payments_received_by
        FOREIGN KEY (received_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE billing_requests (
    id INT NOT NULL AUTO_INCREMENT,
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
    PRIMARY KEY (id),
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
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_billing_requests_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_billing_requests_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_billing_requests_requested_by
        FOREIGN KEY (requested_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_billing_requests_suggested_item
        FOREIGN KEY (suggested_billable_item_id) REFERENCES billable_items(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_billing_requests_reviewed_by
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_billing_requests_patient_charge
        FOREIGN KEY (patient_charge_id) REFERENCES patient_charges(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_billing', 'View Billing', 'Billing', 'View patient billing and invoices.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_billing');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_patient_charge', 'Create Patient Charge', 'Billing', 'Create patient charges from billable items.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_patient_charge');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'cancel_patient_charge', 'Cancel Patient Charge', 'Billing', 'Cancel patient charges where allowed.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'cancel_patient_charge');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_invoice', 'Create Invoice', 'Billing', 'Create and refresh patient invoices.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_invoice');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'record_payment', 'Record Payment', 'Billing', 'Record patient payments.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'record_payment');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_receipts', 'View Receipts', 'Billing', 'View and print payment receipts.', 1
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
  AND p.permission_key = 'view_billing';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN (
    'Accounts',
    'Accountant',
    'Receptionist',
    'Records Officer'
  )
  AND p.permission_key = 'view_receipts';

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

/*
|--------------------------------------------------------------------------
| Phase 4.2 Store / Inventory
|--------------------------------------------------------------------------
*/

CREATE TABLE inventory_items (
    id INT NOT NULL AUTO_INCREMENT,
    item_code VARCHAR(30) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    description TEXT NULL,
    billable_item_id INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NOT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_inventory_items_code (item_code),
    KEY idx_inventory_items_name (item_name),
    KEY idx_inventory_items_category (category),
    KEY idx_inventory_items_billable_item (billable_item_id),
    KEY idx_inventory_items_status (is_active),
    KEY idx_inventory_items_created_at (created_at),
    KEY idx_inventory_items_created_by (created_by),
    KEY idx_inventory_items_updated_by (updated_by),
    CONSTRAINT fk_inventory_items_billable_item
        FOREIGN KEY (billable_item_id) REFERENCES billable_items(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_inventory_items_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_inventory_items_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_transactions (
    id BIGINT NOT NULL AUTO_INCREMENT,
    inventory_item_id INT NOT NULL,
    transaction_type ENUM('Receipt','Issue','Return','Adjustment','Consumption') NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    from_department_id INT NULL,
    to_department_id INT NULL,
    reference VARCHAR(255) NULL,
    remarks TEXT NULL,
    performed_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_stock_transactions_item (inventory_item_id),
    KEY idx_stock_transactions_type (transaction_type),
    KEY idx_stock_transactions_from_department (from_department_id),
    KEY idx_stock_transactions_to_department (to_department_id),
    KEY idx_stock_transactions_created_at (created_at),
    KEY idx_stock_transactions_performed_by (performed_by),
    CONSTRAINT fk_stock_transactions_item
        FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_stock_transactions_from_department
        FOREIGN KEY (from_department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_stock_transactions_to_department
        FOREIGN KEY (to_department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_stock_transactions_performed_by
        FOREIGN KEY (performed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE department_stock_balances (
    inventory_item_id INT NOT NULL,
    department_id INT NOT NULL,
    quantity DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (inventory_item_id, department_id),
    KEY idx_department_stock_balances_department (department_id),
    KEY idx_department_stock_balances_updated_at (updated_at),
    CONSTRAINT fk_department_stock_balances_item
        FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_department_stock_balances_department
        FOREIGN KEY (department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_stock_usage (
    id BIGINT NOT NULL AUTO_INCREMENT,
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
    PRIMARY KEY (id),
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

CREATE TABLE stock_requests (
    id BIGINT NOT NULL AUTO_INCREMENT,
    requesting_department_id INT NOT NULL,
    requested_by INT NOT NULL,
    status ENUM('Pending','Approved','Issued','Partially Issued','Cancelled') NOT NULL DEFAULT 'Pending',
    reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    cancelled_by INT NULL,
    cancelled_at DATETIME NULL,
    cancel_reason TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_stock_requests_department_status (requesting_department_id, status),
    KEY idx_stock_requests_requested_by_created (requested_by, created_at),
    KEY idx_stock_requests_status_created (status, created_at),
    KEY idx_stock_requests_reviewed_by (reviewed_by),
    KEY idx_stock_requests_cancelled_by (cancelled_by),
    CONSTRAINT fk_stock_requests_department
        FOREIGN KEY (requesting_department_id) REFERENCES departments(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_stock_requests_requested_by
        FOREIGN KEY (requested_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_stock_requests_reviewed_by
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_stock_requests_cancelled_by
        FOREIGN KEY (cancelled_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_request_items (
    id BIGINT NOT NULL AUTO_INCREMENT,
    stock_request_id BIGINT NOT NULL,
    inventory_item_id INT NOT NULL,
    quantity_requested DECIMAL(12,2) NOT NULL,
    quantity_issued DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_stock_request_items_request (stock_request_id),
    KEY idx_stock_request_items_item (inventory_item_id),
    CONSTRAINT fk_stock_request_items_request
        FOREIGN KEY (stock_request_id) REFERENCES stock_requests(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_stock_request_items_item
        FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_inventory', 'View Inventory', 'Store', 'View inventory items and stock balances.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_inventory');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'manage_inventory_items', 'Manage Inventory Items', 'Store', 'Create and edit inventory items.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'manage_inventory_items');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'receive_stock', 'Receive Stock', 'Store', 'Receive stock into store inventory.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'receive_stock');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'issue_stock', 'Issue Stock', 'Store', 'Issue stock to departments.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'issue_stock');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'return_stock', 'Return Stock', 'Store', 'Return stock from departments.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'return_stock');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'adjust_stock', 'Adjust Stock', 'Store', 'Record stock adjustments.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'adjust_stock');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_stock_ledger', 'View Stock Ledger', 'Store', 'View stock movement ledger.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_stock_ledger');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_patient_stock_usage', 'View Patient Stock Usage', 'Store', 'View patient-linked department stock usage records.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_patient_stock_usage');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'record_patient_stock_usage', 'Record Patient Stock Usage', 'Store', 'Record department stock used for a patient encounter.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'record_patient_stock_usage');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_stock_requests', 'View Stock Requests', 'Store', 'View department stock requests.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_stock_requests');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_stock_request', 'Create Stock Request', 'Store', 'Create a department stock request.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_stock_request');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'review_stock_request', 'Review Stock Request', 'Store', 'Approve department stock requests.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'review_stock_request');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'issue_stock_request', 'Issue Stock Request', 'Store', 'Issue stock against a department stock request.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'issue_stock_request');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'cancel_stock_request', 'Cancel Stock Request', 'Store', 'Cancel pending or approved stock requests.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'cancel_stock_request');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Store Officer'
  AND p.permission_key IN (
      'view_inventory',
      'manage_inventory_items',
      'receive_stock',
      'issue_stock',
      'return_stock',
      'adjust_stock',
      'view_stock_ledger'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Accountant','Doctor','Nurse','Laboratory Scientist','Radiographer','Physiotherapist','Theatre Staff','Pharmacist','Receptionist','Records Officer')
  AND p.permission_key = 'view_inventory';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Accountant'
  AND p.permission_key = 'view_stock_ledger';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('Nurse','Doctor','Laboratory Scientist','Radiographer','Physiotherapist','Theatre Staff','Pharmacist')
  AND p.permission_key IN ('view_stock_requests','create_stock_request','cancel_stock_request');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Store Officer'
  AND p.permission_key IN ('view_stock_requests','create_stock_request','review_stock_request','issue_stock_request','cancel_stock_request');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Orderly'
  AND p.permission_key IN ('view_stock_requests','create_stock_request');

/*
|--------------------------------------------------------------------------  
| Phase 4.3 Pharmacy / Dispensing
|--------------------------------------------------------------------------  
*/

CREATE TABLE prescriptions (
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

CREATE TABLE pharmacy_dispensing (
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

CREATE TABLE medication_administration_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NULL,
    visit_id INT NOT NULL,
    patient_id INT NOT NULL,
    medication_name VARCHAR(255) NOT NULL,
    scheduled_time DATETIME NOT NULL,
    dose_given VARCHAR(100) NOT NULL,
    route VARCHAR(100) NULL,
    administration_status ENUM('Given','Missed','Refused','Held') NOT NULL DEFAULT 'Given',
    notes TEXT NULL,
    administered_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_mar_prescription (prescription_id),
    KEY idx_mar_visit_time (visit_id, scheduled_time),
    KEY idx_mar_patient_time (patient_id, scheduled_time),
    KEY idx_mar_status_time (administration_status, scheduled_time),
    KEY idx_mar_administered_by_time (administered_by, scheduled_time),
    CONSTRAINT fk_mar_prescription FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_mar_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mar_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mar_administered_by FOREIGN KEY (administered_by) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE diabetes_monitoring (
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
    CONSTRAINT fk_diabetes_monitoring_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_diabetes_monitoring_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_diabetes_monitoring_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
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

/*
|--------------------------------------------------------------------------  
| Phase 4.5 Basic Dashboards / Reports
|--------------------------------------------------------------------------  
*/

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_reports', 'View Reports', 'Reports', 'View the basic reports module.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_reports');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_financial_reports', 'View Financial Reports', 'Reports', 'View Billing financial summaries.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_financial_reports');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_inventory_reports', 'View Inventory Reports', 'Reports', 'View Store inventory summaries.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_inventory_reports');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_clinical_reports', 'View Clinical Reports', 'Reports', 'View aggregate clinical activity summaries.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_clinical_reports');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('System Administrator','Accounts','Accountant','Store Officer','Doctor','Nurse','Laboratory Scientist','Radiographer','Physiotherapist','Theatre Staff','Pharmacist','Records Officer')
  AND p.permission_key = 'view_reports';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('System Administrator','Accounts','Accountant')
  AND p.permission_key = 'view_financial_reports';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('System Administrator','Store Officer')
  AND p.permission_key = 'view_inventory_reports';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('System Administrator','Doctor','Nurse','Laboratory Scientist','Radiographer','Physiotherapist','Theatre Staff','Pharmacist','Records Officer')
  AND p.permission_key = 'view_clinical_reports';

/*
|--------------------------------------------------------------------------
| Inpatient Admissions / Ward & Bed Workflow
|--------------------------------------------------------------------------
*/

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
    CONSTRAINT fk_wards_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_wards_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_wards_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
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
    CONSTRAINT fk_ward_beds_ward FOREIGN KEY (ward_id) REFERENCES wards(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ward_beds_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_ward_beds_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
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
    CONSTRAINT fk_admissions_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admissions_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admissions_ward FOREIGN KEY (ward_id) REFERENCES wards(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admissions_bed FOREIGN KEY (bed_id) REFERENCES ward_beds(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admissions_admitted_by FOREIGN KEY (admitted_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admissions_discharged_by FOREIGN KEY (discharged_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
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
    CONSTRAINT fk_admission_movements_admission FOREIGN KEY (admission_id) REFERENCES admissions(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admission_movements_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admission_movements_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_admission_movements_from_ward FOREIGN KEY (from_ward_id) REFERENCES wards(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_admission_movements_from_bed FOREIGN KEY (from_bed_id) REFERENCES ward_beds(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_admission_movements_to_ward FOREIGN KEY (to_ward_id) REFERENCES wards(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_admission_movements_to_bed FOREIGN KEY (to_bed_id) REFERENCES ward_beds(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_admission_movements_performed_by FOREIGN KEY (performed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
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

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'use_consultation_handwriting', 'Use Consultation Handwriting', 'Consultation', 'Use the handwriting/touch-pad entry mode on Consultation forms.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'use_consultation_handwriting');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('System Administrator', 'Doctor')
  AND p.permission_key = 'use_consultation_handwriting';
