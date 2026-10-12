UPDATE billable_items
SET is_active = 0,
    updated_at = NOW()
WHERE item_code IN ('REG-FAMILY-CARD', 'REG-FAMILY-RENEWAL');

ALTER TABLE patient_registration_billing_requests
    DROP FOREIGN KEY fk_registration_billing_family_card;

ALTER TABLE patient_registration_billing_requests
    DROP KEY idx_registration_billing_family_card,
    DROP COLUMN family_card_id,
    MODIFY billing_type ENUM('InitialRegistration','MonthlyRenewal') NOT NULL;

DROP TABLE IF EXISTS family_card_members;
DROP TABLE IF EXISTS family_cards;
