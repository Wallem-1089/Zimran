/*
 * Reverses only settings metadata introduced by Milestone 2.3.1.
 * Clinical safety records and histories are not modified.
 */

DELETE FROM system_settings
WHERE setting_key = 'clinical_safety.allow_self_allergy_verification';

UPDATE system_settings
SET validation_rules = '{"required":true}'
WHERE setting_key IN (
    'clinical_safety.allergy_types',
    'clinical_safety.severity_values',
    'clinical_safety.alert_types',
    'clinical_safety.alert_priorities',
    'clinical_safety.confidentiality_levels'
);
