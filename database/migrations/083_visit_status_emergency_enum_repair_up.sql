ALTER TABLE visits
    MODIFY visit_status ENUM(
        'Waiting',
        'Reception',
        'Records',
        'Nursing',
        'Doctor',
        'Emergency',
        'Laboratory',
        'Radiology',
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
