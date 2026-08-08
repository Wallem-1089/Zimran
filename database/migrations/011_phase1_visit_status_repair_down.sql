/*
|--------------------------------------------------------------------------
| Rollback Phase 1 - Milestone 1.7 Invalid Encounter Status Repair
|--------------------------------------------------------------------------
*/

SET @phase1_previous_sql_mode = @@SESSION.sql_mode;

SET SESSION sql_mode = REPLACE(
    REPLACE(@@SESSION.sql_mode, 'STRICT_TRANS_TABLES', ''),
    'STRICT_ALL_TABLES',
    ''
);

UPDATE visits v
INNER JOIN phase1_visit_status_repair r ON r.visit_id = v.id
SET v.visit_status = r.previous_status;

SET SESSION sql_mode = @phase1_previous_sql_mode;

DROP TABLE phase1_visit_status_repair;
