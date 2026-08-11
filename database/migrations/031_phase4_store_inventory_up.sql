SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS inventory_items (
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

CREATE TABLE IF NOT EXISTS stock_transactions (
    id BIGINT NOT NULL AUTO_INCREMENT,
    inventory_item_id INT NOT NULL,
    transaction_type ENUM('Receipt','Issue','Return','Adjustment') NOT NULL,
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

CREATE TABLE IF NOT EXISTS department_stock_balances (
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

SET FOREIGN_KEY_CHECKS = 1;
