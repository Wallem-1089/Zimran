-- Remove patient soft-delete / void metadata.
-- This is destructive to deletion metadata and should only be used with an approved backup.

DROP INDEX IF EXISTS idx_patients_deleted ON patients;

ALTER TABLE patients
    DROP COLUMN IF EXISTS deletion_reason,
    DROP COLUMN IF EXISTS deleted_by,
    DROP COLUMN IF EXISTS deleted_at,
    DROP COLUMN IF EXISTS is_deleted;
