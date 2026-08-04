/*
|--------------------------------------------------------------------------
| Phase 0 - Live Schema Alignment
|--------------------------------------------------------------------------
|
| This migration aligns the existing development database with the
| encounter workflow services without removing existing columns.
| It is intended for the current hospital_management_system database.
|--------------------------------------------------------------------------
*/

START TRANSACTION;

ALTER TABLE visits

    ADD COLUMN queue_number INT NULL
        AFTER attending_doctor_id,

    ADD INDEX idx_visits_department_receive
        (current_department_id, current_department_received_status);

ALTER TABLE visit_transfers

    ADD COLUMN previous_status VARCHAR(50) NULL
        AFTER to_status,

    ADD COLUMN new_status VARCHAR(50) NULL
        AFTER previous_status,

    ADD INDEX idx_transfer_pending
        (visit_id, received_at, transferred_at),

    ADD INDEX idx_transfer_destination_pending
        (to_department_id, received_at, transferred_at);

UPDATE visit_transfers

SET

    previous_status = from_status,

    new_status = to_status

WHERE previous_status IS NULL

   OR new_status IS NULL;

CREATE TABLE encounter_events (

    id INT AUTO_INCREMENT PRIMARY KEY,

    visit_id INT NOT NULL,

    event_type VARCHAR(100) NOT NULL,

    event_title VARCHAR(150) NOT NULL,

    event_description TEXT NULL,

    department_id INT NULL,

    performed_by INT NULL,

    event_time DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    created_at TIMESTAMP
        DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_encounter_events_visit_time
        (visit_id, event_time, id),

    INDEX idx_encounter_events_department
        (department_id),

    INDEX idx_encounter_events_performed_by
        (performed_by),

    CONSTRAINT fk_encounter_events_visit
        FOREIGN KEY (visit_id)
        REFERENCES visits(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_encounter_events_department
        FOREIGN KEY (department_id)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_encounter_events_performed_by
        FOREIGN KEY (performed_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

COMMIT;
