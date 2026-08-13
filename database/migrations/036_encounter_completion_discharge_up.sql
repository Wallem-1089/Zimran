ALTER TABLE visits
    ADD COLUMN IF NOT EXISTS completed_at DATETIME NULL AFTER updated_at,
    ADD COLUMN IF NOT EXISTS completed_by INT NULL AFTER completed_at,
    ADD COLUMN IF NOT EXISTS discharge_diagnosis TEXT NULL AFTER completed_by,
    ADD COLUMN IF NOT EXISTS discharge_notes TEXT NULL AFTER discharge_diagnosis,
    ADD COLUMN IF NOT EXISTS follow_up_instructions TEXT NULL AFTER discharge_notes,
    ADD INDEX IF NOT EXISTS idx_visits_completed_at (completed_at),
    ADD INDEX IF NOT EXISTS idx_visits_completed_by (completed_by);
