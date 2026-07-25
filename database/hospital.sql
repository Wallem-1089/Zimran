/*
|--------------------------------------------------------------------------
| Hospital Management System
|--------------------------------------------------------------------------
| Database Schema
| Part 1
|
| Tables
|   • departments
|   • roles
|   • users
|
*/

DROP DATABASE IF EXISTS hospital_management_system;

CREATE DATABASE hospital_management_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE hospital_management_system;





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

    hospital_number VARCHAR(30) NOT NULL,

    first_name VARCHAR(100) NOT NULL,

    middle_name VARCHAR(100) NULL,

    last_name VARCHAR(100) NOT NULL,

    gender ENUM(
        'Male',
        'Female'
    ) NOT NULL,

    date_of_birth DATE NOT NULL,

    marital_status VARCHAR(30) NULL,

    occupation VARCHAR(100) NULL,

    phone VARCHAR(20) NULL,

    email VARCHAR(150) NULL,

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
| Audit Logs
|--------------------------------------------------------------------------
*/

CREATE TABLE audit_logs (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    visit_id INT NULL,

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

    queue_status ENUM(

        'Waiting',

        'In Progress',

        'Completed'

    )
    NOT NULL
    DEFAULT 'Waiting',

    queued_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_queue_visit
        (visit_id),

    INDEX idx_queue_department
        (department_id),

    INDEX idx_queue_status
        (queue_status),

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