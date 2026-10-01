DROP TABLE IF EXISTS patient_registration_billing_requests;

DELETE FROM billable_items
WHERE item_code IN ('REG-NORMAL', 'REG-EMERGENCY', 'REG-RENEWAL');

ALTER TABLE patients
    DROP INDEX idx_patients_registration_status,
    DROP INDEX idx_patients_registration_valid_until,
    DROP COLUMN registration_completed_at,
    DROP COLUMN registration_valid_until,
    DROP COLUMN registration_paid_at,
    DROP COLUMN registration_fee,
    DROP COLUMN registration_type,
    DROP COLUMN registration_status,
    MODIFY gender ENUM('Male','Female','Other','Unknown') NOT NULL,
    MODIFY date_of_birth DATE NOT NULL;
