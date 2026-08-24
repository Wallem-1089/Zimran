SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS external_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_number VARCHAR(40) NOT NULL UNIQUE,
    customer_name VARCHAR(150) NULL,
    customer_phone VARCHAR(50) NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('Cash','Card','Transfer','Other') NOT NULL DEFAULT 'Cash',
    reference VARCHAR(255) NULL,
    sold_by INT NOT NULL,
    status ENUM('Completed','Cancelled') NOT NULL DEFAULT 'Completed',
    cancelled_by INT NULL,
    cancelled_at DATETIME NULL,
    cancel_reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_external_sales_number (sale_number),
    KEY idx_external_sales_status (status),
    KEY idx_external_sales_sold_by (sold_by),
    KEY idx_external_sales_created_at (created_at),
    CONSTRAINT fk_external_sales_sold_by
        FOREIGN KEY (sold_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_external_sales_cancelled_by
        FOREIGN KEY (cancelled_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS external_sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    external_sale_id INT NOT NULL,
    inventory_item_id INT NOT NULL,
    billable_item_id INT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity DECIMAL(12,2) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_external_sale_items_sale (external_sale_id),
    KEY idx_external_sale_items_inventory_item (inventory_item_id),
    KEY idx_external_sale_items_billable_item (billable_item_id),
    CONSTRAINT fk_external_sale_items_sale
        FOREIGN KEY (external_sale_id) REFERENCES external_sales(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_external_sale_items_inventory_item
        FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_external_sale_items_billable_item
        FOREIGN KEY (billable_item_id) REFERENCES billable_items(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_external_sales', 'View External Store Sales', 'Store', 'View external/non-patient store sales and receipts.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_external_sales');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_external_sale', 'Create External Store Sale', 'Store', 'Sell store stock to an external non-patient customer.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_external_sale');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'cancel_external_sale', 'Cancel External Store Sale', 'Store', 'Cancel an external store sale without deleting its record.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'cancel_external_sale');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_external_sale_receipts', 'View External Store Sale Receipts', 'Store', 'View printable external store sale receipts.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_external_sale_receipts');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Store Officer'
  AND p.permission_key IN (
      'view_external_sales',
      'create_external_sale',
      'cancel_external_sale',
      'view_external_sale_receipts'
  );

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Accountant'
  AND p.permission_key IN (
      'view_external_sales',
      'view_external_sale_receipts'
  );

SET FOREIGN_KEY_CHECKS = 1;
