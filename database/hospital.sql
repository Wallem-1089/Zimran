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

('Theatre', 'Surgical theatre'),

('Accounts', 'Billing and payments'),

('Store', 'Medical store');






/*
|--------------------------------------------------------------------------
| Roles
|--------------------------------------------------------------------------
*/

CREATE TABLE roles (

    id INT AUTO_INCREMENT PRIMARY KEY,

    role_name VARCHAR(100) NOT NULL,

    description TEXT NULL,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL
        DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_roles_name
        UNIQUE (role_name)

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

('Theatre Staff','Surgical procedures'),

('Accountant','Billing and payments'),

('Store Officer','Medical store');







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

    phone VARCHAR(20) NULL,

    normalized_phone VARCHAR(30) NULL,

    email VARCHAR(150) NULL,

    normalized_email VARCHAR(150) NULL,

    address TEXT NULL,

    state_of_origin VARCHAR(100) NULL,

    nationality VARCHAR(100) NULL,

    blood_group VARCHAR(5) NULL,

    genotype VARCHAR(5) NULL,

    allergies TEXT NULL,

    next_of_kin VARCHAR(150) NULL,

    next_of_kin_relationship VARCHAR(100) NULL,

    next_of_kin_phone VARCHAR(20) NULL,

    registered_by INT NULL,

    demographic_version INT NOT NULL DEFAULT 1,

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
