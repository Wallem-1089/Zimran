/*
|--------------------------------------------------------------------------
| Phase 0 - Store Department Status Compatibility
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
        'Store',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Waiting';

