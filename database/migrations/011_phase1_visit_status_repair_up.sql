/*
|--------------------------------------------------------------------------
| Phase 1 - Milestone 1.7 Invalid Encounter Status Repair
|--------------------------------------------------------------------------
*/

CREATE TABLE phase1_visit_status_repair (

    visit_id INT NOT NULL PRIMARY KEY,

    previous_status VARCHAR(50) NOT NULL,

    repaired_status VARCHAR(50) NOT NULL,

    repaired_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_phase1_status_repair_visit
        FOREIGN KEY (visit_id)
        REFERENCES visits(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO phase1_visit_status_repair (
    visit_id,
    previous_status,
    repaired_status
)
SELECT
    v.id,
    v.visit_status,
    CASE
        WHEN d.department_name IN (
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
            'Store'
        ) THEN d.department_name
        ELSE 'Waiting'
    END
FROM visits v
LEFT JOIN departments d ON d.id = v.current_department_id
WHERE v.visit_status = '';

UPDATE visits v
INNER JOIN phase1_visit_status_repair r ON r.visit_id = v.id
SET v.visit_status = r.repaired_status;
