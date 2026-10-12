-- Family Card registration and renewal support.

CREATE TABLE IF NOT EXISTS family_cards (
    id INT NOT NULL AUTO_INCREMENT,
    card_number VARCHAR(40) NOT NULL,
    principal_patient_id INT NULL,
    primary_contact_name VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    address TEXT NULL,
    status ENUM('PendingPayment','Active','Suspended','Expired','Cancelled') NOT NULL DEFAULT 'PendingPayment',
    activation_fee DECIMAL(12,2) NOT NULL DEFAULT 50000.00,
    renewal_fee DECIMAL(12,2) NOT NULL DEFAULT 20000.00,
    activated_at DATETIME NULL,
    valid_until DATE NULL,
    created_by INT NOT NULL,
    updated_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_family_cards_number (card_number),
    KEY idx_family_cards_principal (principal_patient_id),
    KEY idx_family_cards_status_valid (status, valid_until),
    CONSTRAINT fk_family_cards_principal_patient
        FOREIGN KEY (principal_patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_family_cards_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_family_cards_updated_by
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS family_card_members (
    id INT NOT NULL AUTO_INCREMENT,
    family_card_id INT NOT NULL,
    patient_id INT NOT NULL,
    relationship VARCHAR(100) NULL,
    member_type ENUM('Principal','Spouse','Child','Dependent','Other') NOT NULL DEFAULT 'Dependent',
    status ENUM('Active','Inactive','Removed') NOT NULL DEFAULT 'Active',
    added_by INT NOT NULL,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    removed_by INT NULL,
    removed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_family_card_members_patient_active (patient_id, status),
    KEY idx_family_card_members_card (family_card_id),
    KEY idx_family_card_members_patient (patient_id),
    CONSTRAINT fk_family_card_members_card
        FOREIGN KEY (family_card_id) REFERENCES family_cards(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_family_card_members_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_family_card_members_added_by
        FOREIGN KEY (added_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_family_card_members_removed_by
        FOREIGN KEY (removed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE patient_registration_billing_requests
    MODIFY billing_type ENUM(
        'InitialRegistration',
        'MonthlyRenewal',
        'FamilyCardActivation',
        'FamilyCardRenewal'
    ) NOT NULL,
    ADD COLUMN family_card_id INT NULL AFTER patient_id,
    ADD KEY idx_registration_billing_family_card (family_card_id),
    ADD CONSTRAINT fk_registration_billing_family_card
        FOREIGN KEY (family_card_id) REFERENCES family_cards(id)
        ON UPDATE CASCADE ON DELETE SET NULL;

INSERT INTO billable_items (
    item_code, item_name, item_type, department_id, description,
    unit_price, unit, is_active, created_by, created_at, updated_at
)
SELECT seed.item_code,
       seed.item_name,
       'Service',
       NULL,
       seed.description,
       seed.unit_price,
       'Registration',
       1,
       u.id,
       NOW(),
       NOW()
FROM (
    SELECT 'REG-FAMILY-CARD' AS item_code, 'Family Card Registration' AS item_name, 50000.00 AS unit_price, 'Family Card activation/registration fee.' AS description
    UNION ALL SELECT 'REG-FAMILY-RENEWAL', 'Family Card Renewal', 20000.00, 'Family Card monthly renewal fee.'
) seed
INNER JOIN users u
    ON u.id = (
        SELECT u2.id
        FROM users u2
        ORDER BY CASE WHEN u2.username = 'walter' THEN 0 ELSE 1 END, u2.id
        LIMIT 1
    )
WHERE NOT EXISTS (
    SELECT 1 FROM billable_items existing WHERE existing.item_code = seed.item_code
);
