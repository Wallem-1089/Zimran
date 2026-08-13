ALTER TABLE visits
    DROP INDEX IF EXISTS idx_visits_completed_by,
    DROP INDEX IF EXISTS idx_visits_completed_at,
    DROP COLUMN IF EXISTS follow_up_instructions,
    DROP COLUMN IF EXISTS discharge_notes,
    DROP COLUMN IF EXISTS discharge_diagnosis,
    DROP COLUMN IF EXISTS completed_by,
    DROP COLUMN IF EXISTS completed_at;
