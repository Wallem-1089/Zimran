/* Phase 3.4 Laboratory result detail enhancement rollback. */

ALTER TABLE laboratory_results
    DROP COLUMN findings,
    DROP COLUMN sample_taken;
