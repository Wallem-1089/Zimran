/*
|--------------------------------------------------------------------------
| Reverse Phase 1.8 Patient Gender Remediation
|--------------------------------------------------------------------------
|
| Contraction is safe only when no patient uses Other or Unknown. The down
| migration refuses to rewrite those values silently.
|
*/

DELIMITER //

CREATE PROCEDURE rollback_phase1_patient_gender_remediation()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM patients
        WHERE gender IN ('Other', 'Unknown')
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot contract patient gender while Other or Unknown records exist.';
    END IF;

    ALTER TABLE patients
        MODIFY gender ENUM(
            'Male',
            'Female'
        ) NOT NULL;

    DROP TABLE phase1_patient_gender_repair;
END//

DELIMITER ;

CALL rollback_phase1_patient_gender_remediation();
DROP PROCEDURE rollback_phase1_patient_gender_remediation;
