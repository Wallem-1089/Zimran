/*
|--------------------------------------------------------------------------
| Phase 0 - Store Department Status Compatibility Rollback
|--------------------------------------------------------------------------
*/

ALTER TABLE visits

    MODIFY visit_status ENUM(
        'Waiting',
        'Reception',
        'Records',
        'Nursing',
        'Doctor',
        'Laboratory',
        'X-Ray',
        'Pharmacy',
        'Physiotherapy',
        'Theatre',
        'Accounts',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Waiting';

