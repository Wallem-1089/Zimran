/*
|--------------------------------------------------------------------------
| Phase 1 - Milestone 1.3 Departments and User Assignments
|--------------------------------------------------------------------------
*/

ALTER TABLE departments

    ADD COLUMN department_code VARCHAR(30) NULL
        AFTER department_name,

    ADD COLUMN location VARCHAR(150) NULL
        AFTER description,

    ADD COLUMN contact_extension VARCHAR(30) NULL
        AFTER location,

    ADD COLUMN department_type ENUM(
        'Clinical',
        'Administrative',
        'Diagnostic',
        'Support'
    ) NOT NULL DEFAULT 'Support'
        AFTER contact_extension,

    ADD COLUMN queue_enabled TINYINT(1) NOT NULL DEFAULT 1
        AFTER department_type,

    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1
        AFTER queue_enabled,

    ADD COLUMN display_order INT NOT NULL DEFAULT 0
        AFTER is_active,

    ADD INDEX idx_departments_active (is_active),

    ADD INDEX idx_departments_type (department_type),

    ADD INDEX idx_departments_queue (queue_enabled);

UPDATE departments
SET department_code = CONCAT('DEPT-', LPAD(id, 3, '0'))
WHERE department_code IS NULL;

UPDATE departments
SET department_type = CASE
    WHEN department_name IN ('Doctor', 'Nursing', 'Reception', 'Records')
        THEN 'Clinical'
    WHEN department_name IN ('Laboratory', 'X-Ray')
        THEN 'Diagnostic'
    WHEN department_name IN ('Administrator', 'Accounts')
        THEN 'Administrative'
    ELSE 'Support'
END,
display_order = id
WHERE department_type = 'Support' AND display_order = 0;

ALTER TABLE departments

    MODIFY department_code VARCHAR(30) NOT NULL,

    ADD CONSTRAINT uq_departments_code UNIQUE (department_code);

CREATE TABLE user_departments (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    department_id INT NOT NULL,

    is_primary TINYINT(1) NOT NULL DEFAULT 0,

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    assigned_by INT NULL,

    CONSTRAINT uq_user_department
        UNIQUE (user_id, department_id),

    INDEX idx_user_departments_user_active
        (user_id, is_active),

    INDEX idx_user_departments_department_active
        (department_id, is_active),

    INDEX idx_user_departments_primary
        (user_id, is_primary),

    CONSTRAINT fk_user_departments_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_user_departments_department
        FOREIGN KEY (department_id)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_user_departments_assigned_by
        FOREIGN KEY (assigned_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO user_departments (
    user_id,
    department_id,
    is_primary,
    is_active,
    assigned_by
)
SELECT id, department_id, 1, 1, NULL
FROM users;
