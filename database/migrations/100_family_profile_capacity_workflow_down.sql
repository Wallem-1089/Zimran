ALTER TABLE patient_registration_billing_requests
    MODIFY patient_id INT NOT NULL;

ALTER TABLE family_cards
    DROP COLUMN member_capacity,
    DROP COLUMN family_name;
