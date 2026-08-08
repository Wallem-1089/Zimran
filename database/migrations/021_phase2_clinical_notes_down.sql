/* Destructive rollback: removes Milestone 2.6 Clinical Note data. Test/disposable databases only. */

DELETE FROM record_amendments WHERE record_type = 'ClinicalNote';
DROP TABLE IF EXISTS clinical_note_versions;
DROP TABLE IF EXISTS clinical_notes;

DELETE rp FROM role_permissions rp INNER JOIN permissions p ON p.id=rp.permission_id
WHERE p.permission_key IN ('view_clinical_notes','create_patient_notes','create_encounter_notes','edit_own_note_drafts','edit_any_note_draft','sign_clinical_notes','amend_signed_notes','approve_note_amendments','mark_note_entered_in_error','view_confidential_notes','view_note_history');
DELETE FROM permissions WHERE permission_key IN ('view_clinical_notes','create_patient_notes','create_encounter_notes','edit_own_note_drafts','edit_any_note_draft','sign_clinical_notes','amend_signed_notes','approve_note_amendments','mark_note_entered_in_error','view_confidential_notes','view_note_history');
DELETE FROM system_settings WHERE setting_key LIKE 'clinical_notes.%';
