-- Roll back blood-related Medical Document type setting additions.

UPDATE system_settings
SET setting_value = '["referral_letter","identity_document","insurance_document","consent_form","external_laboratory_result","external_radiology_report","discharge_document","clinical_photograph","medical_certificate","correspondence","other"]',
    default_value = '["referral_letter","identity_document","insurance_document","consent_form","external_laboratory_result","external_radiology_report","discharge_document","clinical_photograph","medical_certificate","correspondence","other"]',
    validation_rules = '{"required":true,"schema_values":["referral_letter","identity_document","insurance_document","consent_form","external_laboratory_result","external_radiology_report","discharge_document","clinical_photograph","medical_certificate","correspondence","other"]}',
    updated_at = CURRENT_TIMESTAMP
WHERE setting_key = 'documents.allowed_types';
