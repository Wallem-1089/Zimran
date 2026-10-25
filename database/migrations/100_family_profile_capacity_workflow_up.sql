-- Family Profile workflow: create and bill a family before adding patients.

ALTER TABLE family_cards
    ADD COLUMN IF NOT EXISTS family_name VARCHAR(255) NULL AFTER card_number,
    ADD COLUMN IF NOT EXISTS member_capacity INT NOT NULL DEFAULT 4 AFTER primary_contact_name;

UPDATE family_cards
SET family_name = COALESCE(NULLIF(family_name, ''), primary_contact_name),
    member_capacity = CASE WHEN member_capacity < 1 THEN 4 ELSE member_capacity END
WHERE family_name IS NULL OR family_name = '' OR member_capacity < 1;

ALTER TABLE patient_registration_billing_requests
    MODIFY patient_id INT NULL;
