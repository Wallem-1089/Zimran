-- User-level permission overrides and Consultation handwriting permission.
-- Role permissions remain the default; user overrides provide per-account Allow/Deny.

CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    effect ENUM('Allow','Deny') NOT NULL DEFAULT 'Allow',
    assigned_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_user_permissions UNIQUE (user_id, permission_id),
    INDEX idx_user_permissions_user (user_id),
    INDEX idx_user_permissions_permission (permission_id),
    INDEX idx_user_permissions_effect (effect),
    CONSTRAINT fk_user_permissions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_user_permissions_permission
        FOREIGN KEY (permission_id) REFERENCES permissions(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_user_permissions_assigned_by
        FOREIGN KEY (assigned_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'use_consultation_handwriting', 'Use Consultation Handwriting', 'Consultation', 'Use the handwriting/touch-pad entry mode on Consultation forms.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'use_consultation_handwriting');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('System Administrator', 'Doctor')
  AND p.permission_key = 'use_consultation_handwriting';
