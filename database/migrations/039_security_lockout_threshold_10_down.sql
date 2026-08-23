-- Revert the failed-login lockout threshold to the original Phase 1 default.

UPDATE system_settings
SET setting_value = '5',
    default_value = '5',
    validation_rules = '{"required":true,"min":1,"max":20}',
    updated_at = NOW()
WHERE setting_key = 'security.lockout_threshold';

