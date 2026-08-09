/* Phase 3.4 Laboratory result detail enhancement. */

ALTER TABLE laboratory_results
    ADD COLUMN sample_taken TEXT NULL AFTER patient_id,
    ADD COLUMN findings TEXT NULL AFTER sample_taken;
