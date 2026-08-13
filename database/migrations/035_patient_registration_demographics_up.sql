ALTER TABLE patients
    ADD COLUMN IF NOT EXISTS place_of_work VARCHAR(150) NULL AFTER occupation,
    ADD COLUMN IF NOT EXISTS ethnic_group VARCHAR(100) NULL AFTER nationality,
    ADD COLUMN IF NOT EXISTS religion VARCHAR(100) NULL AFTER ethnic_group,
    ADD COLUMN IF NOT EXISTS next_of_kin_address TEXT NULL AFTER next_of_kin_phone;
