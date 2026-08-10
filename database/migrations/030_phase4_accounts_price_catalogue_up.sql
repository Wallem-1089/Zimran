SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS billable_items (
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

SET FOREIGN_KEY_CHECKS = 1;
