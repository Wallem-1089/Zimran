-- Manual Legacy / Existing Hospital Patient registration support.

ALTER TABLE patients
    MODIFY registration_type ENUM('Normal','Emergency','Legacy') NOT NULL DEFAULT 'Normal',
    ADD COLUMN legacy_hospital_number VARCHAR(50) NULL AFTER hospital_number,
    ADD COLUMN legacy_first_seen_date DATE NULL AFTER legacy_hospital_number,
    ADD COLUMN legacy_notes TEXT NULL AFTER legacy_first_seen_date,
    ADD KEY idx_patients_legacy_hospital_number (legacy_hospital_number);

ALTER TABLE patient_registration_billing_requests
    MODIFY registration_type ENUM('Normal','Emergency','Legacy') NULL;
