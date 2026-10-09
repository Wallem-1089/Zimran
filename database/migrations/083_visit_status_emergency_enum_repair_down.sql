UPDATE visits
SET visit_status = 'Doctor'
WHERE visit_status = 'Emergency';

UPDATE visits
SET visit_status = 'X-Ray'
WHERE visit_status = 'Radiology';

ALTER TABLE visits
    MODIFY visit_status ENUM(
        'Waiting',
        'Reception',
        'Records',
        'Nursing',
        'Doctor',
        'Laboratory',
        'X-Ray',
        'ECG',
        'Plaster',
        'POP',
        'Pharmacy',
        'Physiotherapy',
        'Theatre',
        'Accounts',
        'Store',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Waiting';
