/*
|--------------------------------------------------------------------------
| Rollback - Phase 2 Milestone 2.1 Medical Records Foundation
|--------------------------------------------------------------------------
|
| WARNING: This rollback removes demographic, amendment, and PHI access
| history. Archive those records before using it outside a disposable system.
|
*/

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p
    ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_medical_record',
    'edit_patient_demographics',
    'view_patient_audit_history'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_medical_record',
    'edit_patient_demographics',
    'view_patient_audit_history'
);

DROP TABLE record_access_logs;

DROP TABLE patient_demographic_history;

DROP TABLE record_amendments;

ALTER TABLE audit_logs
    DROP FOREIGN KEY fk_audit_patient,
    DROP INDEX idx_audit_patient_created,
    DROP COLUMN patient_id;

ALTER TABLE patients
    DROP INDEX idx_patients_demographic_version,
    DROP COLUMN demographic_version;
