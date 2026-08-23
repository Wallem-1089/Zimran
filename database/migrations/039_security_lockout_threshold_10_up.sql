-- Increase the failed-login lockout threshold from 5 to 10 attempts.

UPDATE system_settings
SET setting_value = '10',
    default_value = '10',
    validation_rules = '{"required":true,"min":1,"max":20}',
    updated_at = NOW()
WHERE setting_key = 'security.lockout_threshold';

