-- Patient soft-delete / void support.
-- Additive only: does not delete patients and does not reset patient or encounter IDs.

ALTER TABLE patients
    ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER demographic_version,
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER is_deleted,
    ADD COLUMN IF NOT EXISTS deleted_by INT NULL AFTER deleted_at,
    ADD COLUMN IF NOT EXISTS deletion_reason TEXT NULL AFTER deleted_by;

CREATE INDEX IF NOT EXISTS idx_patients_deleted
    ON patients (is_deleted, deleted_at);
