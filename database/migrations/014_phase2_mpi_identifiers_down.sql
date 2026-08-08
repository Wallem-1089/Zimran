/* Destructive rollback: archive identifier and duplicate history first. */
DELETE rp FROM role_permissions rp INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN ('view_patient_identifiers','manage_patient_identifiers','verify_patient_identifiers','view_duplicate_candidates','review_duplicate_candidates');
DELETE FROM permissions WHERE permission_key IN ('view_patient_identifiers','manage_patient_identifiers','verify_patient_identifiers','view_duplicate_candidates','review_duplicate_candidates');
DELETE FROM system_settings WHERE setting_key LIKE 'mpi.%';
DROP TABLE patient_duplicate_candidates;
DROP TABLE patient_identifier_history;
DROP TABLE patient_identifiers;
ALTER TABLE patients
 DROP INDEX idx_patients_normalized_name,
 DROP INDEX idx_patients_normalized_phone,
 DROP INDEX idx_patients_normalized_email,
 DROP INDEX idx_patients_dob_normalized_name,
 DROP COLUMN normalized_first_name,
 DROP COLUMN normalized_middle_name,
 DROP COLUMN normalized_last_name,
 DROP COLUMN normalized_phone,
 DROP COLUMN normalized_email;
