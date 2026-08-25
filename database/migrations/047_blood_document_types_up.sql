-- Add blood-related Medical Document types used by the Patient Chart Blood Card.

UPDATE system_settings
SET setting_value = '["referral_letter","identity_document","insurance_document","consent_form","external_laboratory_result","blood_card","blood_group_result","crossmatch_form","transfusion_record","external_radiology_report","discharge_document","clinical_photograph","medical_certificate","correspondence","other"]',
    default_value = '["referral_letter","identity_document","insurance_document","consent_form","external_laboratory_result","blood_card","blood_group_result","crossmatch_form","transfusion_record","external_radiology_report","discharge_document","clinical_photograph","medical_certificate","correspondence","other"]',
    validation_rules = '{"required":true,"schema_values":["referral_letter","identity_document","insurance_document","consent_form","external_laboratory_result","blood_card","blood_group_result","crossmatch_form","transfusion_record","external_radiology_report","discharge_document","clinical_photograph","medical_certificate","correspondence","other"]}',
    updated_at = CURRENT_TIMESTAMP
WHERE setting_key = 'documents.allowed_types';
