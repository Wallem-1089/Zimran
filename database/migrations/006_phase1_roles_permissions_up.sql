/*
|--------------------------------------------------------------------------
| Phase 1 - Milestone 1.2 Roles and Permissions
|--------------------------------------------------------------------------
*/

ALTER TABLE roles

    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1
        AFTER description,

    ADD INDEX idx_roles_active (is_active);

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

    CONSTRAINT uq_role_permissions
        UNIQUE (role_id, permission_id),

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

INSERT INTO permissions (
    permission_key,
    permission_name,
    module,
    description
)
VALUES
    ('view_encounter', 'View Encounters', 'Visits', 'View encounter workspaces.'),
    ('create_encounter', 'Create Encounters', 'Visits', 'Create new encounters.'),
    ('transfer_encounter', 'Transfer Encounters', 'Visits', 'Transfer encounters between departments.'),
    ('receive_encounter', 'Receive Encounters', 'Visits', 'Receive transferred encounters.'),
    ('assign_doctor', 'Assign Doctor', 'Visits', 'Assign a doctor to an encounter.'),
    ('change_encounter_status', 'Change Encounter Status', 'Visits', 'Change encounter lifecycle status.'),
    ('edit_encounter', 'Edit Encounters', 'Visits', 'Edit active encounter data.'),
    ('manage_users', 'Manage Users', 'Administration', 'Create and administer user accounts.'),
    ('manage_roles', 'Manage Roles', 'Administration', 'Create and administer roles.'),
    ('manage_permissions', 'Manage Permissions', 'Administration', 'Assign and administer permissions.')
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name <> 'System Administrator'
  AND p.permission_key IN (
      'view_encounter',
      'transfer_encounter',
      'receive_encounter',
      'change_encounter_status',
      'edit_encounter'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Receptionist'
  AND p.permission_key = 'create_encounter';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.role_name = 'Doctor'
  AND p.permission_key = 'assign_doctor';
