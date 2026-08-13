ALTER TABLE patients
    DROP COLUMN IF EXISTS next_of_kin_address,
    DROP COLUMN IF EXISTS religion,
    DROP COLUMN IF EXISTS ethnic_group,
    DROP COLUMN IF EXISTS place_of_work;
