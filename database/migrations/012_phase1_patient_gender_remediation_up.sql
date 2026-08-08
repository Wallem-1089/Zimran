/*
|--------------------------------------------------------------------------
| Phase 1.8 Patient Gender Remediation
|--------------------------------------------------------------------------
|
| Expands the patient gender domain without removing existing values. Any
| historical empty ENUM sentinels created by permissive SQL mode are recorded
| before being explicitly repaired to Unknown.
|
*/

CREATE TABLE phase1_patient_gender_repair (
    patient_id INT NOT NULL,
    previous_gender VARCHAR(30) NOT NULL,
    repaired_gender VARCHAR(30) NOT NULL,
    repaired_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (patient_id),

    CONSTRAINT fk_phase1_patient_gender_repair_patient
        FOREIGN KEY (patient_id)
        REFERENCES patients(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO phase1_patient_gender_repair (
    patient_id,
    previous_gender,
    repaired_gender
)
SELECT
    id,
    '<EMPTY_ENUM_SENTINEL>',
    'Unknown'
FROM patients
WHERE gender = '';

ALTER TABLE patients
    MODIFY gender ENUM(
        'Male',
        'Female',
        'Other',
        'Unknown'
    ) NOT NULL;

UPDATE patients p
INNER JOIN phase1_patient_gender_repair r
    ON r.patient_id = p.id
SET p.gender = r.repaired_gender
WHERE p.gender = '';
