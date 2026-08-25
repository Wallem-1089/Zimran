SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS stock_requests (
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

CREATE TABLE IF NOT EXISTS stock_request_items (
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
WHERE r.role_name IN ('Nurse','Doctor','Laboratory Scientist','Radiographer','Physiotherapist','Theatre Staff','Pharmacist')
  AND p.permission_key IN ('view_stock_requests','create_stock_request','cancel_stock_request');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name = 'Store Officer'
  AND p.permission_key IN ('view_stock_requests','create_stock_request','review_stock_request','issue_stock_request','cancel_stock_request');

SET FOREIGN_KEY_CHECKS = 1;
