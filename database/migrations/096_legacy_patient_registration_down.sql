ALTER TABLE patient_registration_billing_requests
    MODIFY registration_type ENUM('Normal','Emergency') NULL;

ALTER TABLE patients
    DROP KEY idx_patients_legacy_hospital_number,
    DROP COLUMN legacy_notes,
    DROP COLUMN legacy_first_seen_date,
    DROP COLUMN legacy_hospital_number,
    MODIFY registration_type ENUM('Normal','Emergency') NOT NULL DEFAULT 'Normal';
