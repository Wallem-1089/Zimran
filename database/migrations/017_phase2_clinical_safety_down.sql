/* Destructive rollback: empty isolated verification databases only. */
DELETE rp FROM role_permissions rp INNER JOIN permissions p ON p.id=rp.permission_id
WHERE p.permission_key IN ('view_clinical_safety','record_allergies','update_allergies','verify_allergies','resolve_allergies','manage_clinical_alerts','view_confidential_alerts','view_clinical_safety_history');
DELETE FROM permissions WHERE permission_key IN ('view_clinical_safety','record_allergies','update_allergies','verify_allergies','resolve_allergies','manage_clinical_alerts','view_confidential_alerts','view_clinical_safety_history');
DELETE FROM system_settings WHERE setting_key IN ('clinical_safety.allergy_types','clinical_safety.severity_values','clinical_safety.nurse_may_verify_allergies','clinical_safety.alert_types','clinical_safety.alert_priorities','clinical_safety.confidentiality_levels','clinical_safety.default_alert_expiry_days','clinical_safety.legacy_allergy_warning');
DROP TABLE patient_alert_history;
DROP TABLE patient_alerts;
DROP TABLE patient_allergy_history;
DROP TABLE patient_allergies;
