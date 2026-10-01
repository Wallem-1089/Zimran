ALTER TABLE payments
    DROP COLUMN IF EXISTS cancel_reason,
    DROP COLUMN IF EXISTS cancelled_at,
    DROP COLUMN IF EXISTS cancelled_by,
    DROP COLUMN IF EXISTS status;

ALTER TABLE billable_items
    MODIFY item_type ENUM('Drug','Consumable','Service','Product') NOT NULL;

UPDATE billable_items
SET item_type = 'Product'
WHERE item_type IN ('Drug','Consumable');

ALTER TABLE billable_items
    MODIFY item_type ENUM('Service','Product') NOT NULL;

ALTER TABLE staff_profiles
    DROP COLUMN IF EXISTS profile_photo_path;
