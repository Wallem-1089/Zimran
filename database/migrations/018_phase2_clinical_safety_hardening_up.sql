/* Phase 2 Milestone 2.3.1: Clinical Safety hardening. */

INSERT INTO system_settings (
    setting_key,
    setting_value,
    setting_type,
    setting_group,
    description,
    default_value,
    validation_rules,
    is_public,
    is_editable,
    is_system,
    sort_order
) VALUES (
    'clinical_safety.allow_self_allergy_verification',
    'false',
    'boolean',
    'Medical Records',
    'Whether an allergy author may verify their own unverified allergy. Disabled by default.',
    'false',
    '{}',
    0,
    1,
    1,
    225
) ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    default_value = VALUES(default_value),
    validation_rules = VALUES(validation_rules);

UPDATE system_settings
SET validation_rules = '{"required":true,"schema_values":["Drug","Food","Environmental","Biological","Other"]}'
WHERE setting_key = 'clinical_safety.allergy_types';

UPDATE system_settings
SET validation_rules = '{"required":true,"schema_values":["Mild","Moderate","Severe","Life-threatening","Unknown"]}'
WHERE setting_key = 'clinical_safety.severity_values';

UPDATE system_settings
SET validation_rules = '{"required":true,"schema_values":["Clinical Risk","Infection Control","Fall Risk","Communication Need","Safeguarding","Special Handling","Other"]}'
WHERE setting_key = 'clinical_safety.alert_types';

UPDATE system_settings
SET validation_rules = '{"required":true,"schema_values":["Low","Medium","High","Critical"]}'
WHERE setting_key = 'clinical_safety.alert_priorities';

UPDATE system_settings
SET validation_rules = '{"required":true,"schema_values":["Standard","Restricted","Confidential"]}'
WHERE setting_key = 'clinical_safety.confidentiality_levels';
