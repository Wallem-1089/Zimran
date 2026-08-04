/*
|--------------------------------------------------------------------------
| Phase 0 - Live Schema Alignment Rollback
|--------------------------------------------------------------------------
|
| Rollback must only be used after preserving encounter event history.
| Dropping encounter_events removes recorded workflow history.
|--------------------------------------------------------------------------
*/

START TRANSACTION;

ALTER TABLE visits

    DROP INDEX idx_visits_department_receive,

    DROP COLUMN queue_number;

ALTER TABLE visit_transfers

    DROP INDEX idx_transfer_pending,

    DROP INDEX idx_transfer_destination_pending,

    DROP COLUMN previous_status,

    DROP COLUMN new_status;

DROP TABLE encounter_events;

COMMIT;
