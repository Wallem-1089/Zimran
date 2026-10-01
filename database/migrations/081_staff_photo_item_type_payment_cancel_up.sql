ALTER TABLE staff_profiles
    ADD COLUMN IF NOT EXISTS profile_photo_path VARCHAR(255) NULL AFTER official_email;

ALTER TABLE billable_items
    MODIFY item_type ENUM('Drug','Consumable','Service','Product') NOT NULL;

UPDATE billable_items
SET item_type = 'Consumable'
WHERE item_type = 'Product';

ALTER TABLE billable_items
    MODIFY item_type ENUM('Drug','Consumable','Service') NOT NULL;

ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS status ENUM('Active','Cancelled') NOT NULL DEFAULT 'Active' AFTER notes,
    ADD COLUMN IF NOT EXISTS cancelled_by INT NULL AFTER received_by,
    ADD COLUMN IF NOT EXISTS cancelled_at DATETIME NULL AFTER cancelled_by,
    ADD COLUMN IF NOT EXISTS cancel_reason TEXT NULL AFTER cancelled_at,
    ADD INDEX IF NOT EXISTS idx_payments_status (status),
    ADD INDEX IF NOT EXISTS idx_payments_cancelled_by (cancelled_by);
