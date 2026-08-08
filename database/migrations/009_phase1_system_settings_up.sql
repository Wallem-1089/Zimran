/*
|--------------------------------------------------------------------------
| Phase 1 - Milestone 1.6 Enterprise System Settings
|--------------------------------------------------------------------------
*/

CREATE TABLE system_settings (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    setting_key VARCHAR(191) NOT NULL,

    setting_value LONGTEXT NULL,

    setting_type VARCHAR(30) NOT NULL DEFAULT 'string',

    setting_group VARCHAR(100) NOT NULL,

    description TEXT NULL,

    default_value LONGTEXT NULL,

    validation_rules TEXT NULL,

    is_public TINYINT(1) NOT NULL DEFAULT 0,

    is_editable TINYINT(1) NOT NULL DEFAULT 1,

    is_system TINYINT(1) NOT NULL DEFAULT 0,

    is_sensitive TINYINT(1) NOT NULL DEFAULT 0,

    is_encrypted TINYINT(1) NOT NULL DEFAULT 0,

    sort_order INT NOT NULL DEFAULT 0,

    created_by INT NULL,

    updated_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL DEFAULT NULL
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_system_settings_key UNIQUE (setting_key),

    INDEX idx_system_settings_group_order
        (setting_group, sort_order, setting_key),

    INDEX idx_system_settings_public
        (is_public, setting_group),

    INDEX idx_system_settings_system
        (is_system, is_editable),

    CONSTRAINT fk_system_settings_created_by
        FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_system_settings_updated_by
        FOREIGN KEY (updated_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_setting_history (

    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    setting_id BIGINT NULL,

    setting_key VARCHAR(191) NOT NULL,

    setting_group VARCHAR(100) NOT NULL,

    action VARCHAR(50) NOT NULL,

    old_value LONGTEXT NULL,

    new_value LONGTEXT NULL,

    is_sensitive TINYINT(1) NOT NULL DEFAULT 0,

    changed_by INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_setting_history_setting_created
        (setting_id, created_at),

    INDEX idx_setting_history_key_created
        (setting_key, created_at),

    INDEX idx_setting_history_group_created
        (setting_group, created_at),

    INDEX idx_setting_history_actor_created
        (changed_by, created_at),

    CONSTRAINT fk_setting_history_setting
        FOREIGN KEY (setting_id)
        REFERENCES system_settings(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_setting_history_changed_by
        FOREIGN KEY (changed_by)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (
    permission_key,
    permission_name,
    module,
    description
) VALUES (
    'manage_settings',
    'Manage System Settings',
    'Administration',
    'View and administer enterprise system settings.'
)
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module = VALUES(module),
    description = VALUES(description);

INSERT INTO system_settings (
    setting_key, setting_value, setting_type, setting_group, description,
    default_value, validation_rules, is_public, is_editable, is_system,
    is_sensitive, sort_order
) VALUES
('hospital.name', 'Hospital Management System', 'string', 'Hospital', 'Official hospital name.', 'Hospital Management System', '{"required":true,"min_length":2,"max_length":150}', 1, 1, 1, 0, 10),
('hospital.code', 'HMS', 'string', 'Hospital', 'Short hospital code.', 'HMS', '{"required":true,"regex":"^[A-Za-z0-9_-]{2,20}$"}', 1, 1, 1, 0, 20),
('hospital.logo', '', 'string', 'Hospital', 'Relative or absolute hospital logo path.', '', '{"max_length":255}', 1, 1, 1, 0, 30),
('hospital.address', '', 'string', 'Hospital', 'Hospital postal address.', '', '{"max_length":500}', 1, 1, 0, 0, 40),
('hospital.contact_phone', '', 'string', 'Hospital', 'Main hospital contact number.', '', '{"max_length":50}', 1, 1, 0, 0, 50),
('hospital.website', '', 'string', 'Hospital', 'Official hospital website.', '', '{"max_length":255}', 1, 1, 0, 0, 60),
('hospital.email', '', 'string', 'Hospital', 'Official hospital email address.', '', '{"max_length":150,"format":"email"}', 1, 1, 0, 0, 70),

('general.timezone', 'Africa/Lagos', 'string', 'General', 'Application timezone.', 'Africa/Lagos', '{"required":true,"format":"timezone"}', 1, 1, 1, 0, 10),
('general.date_format', 'd M Y', 'string', 'General', 'PHP date display format.', 'd M Y', '{"required":true,"max_length":30}', 1, 1, 1, 0, 20),
('general.time_format', 'H:i', 'string', 'General', 'PHP time display format.', 'H:i', '{"required":true,"max_length":30}', 1, 1, 1, 0, 30),
('general.currency', 'NGN', 'string', 'General', 'Default ISO currency code.', 'NGN', '{"required":true,"regex":"^[A-Z]{3}$"}', 1, 1, 1, 0, 40),
('general.language', 'en', 'string', 'General', 'Default application language.', 'en', '{"required":true,"allowed":["en"]}', 1, 1, 1, 0, 50),

('security.session_timeout_minutes', '30', 'integer', 'Security', 'Idle session timeout in minutes.', '30', '{"required":true,"min":5,"max":1440}', 0, 1, 1, 0, 10),
('security.password_min_length', '8', 'integer', 'Security', 'Minimum user password length.', '8', '{"required":true,"min":8,"max":128}', 0, 1, 1, 0, 20),
('security.password_complexity', 'basic', 'string', 'Security', 'Password complexity policy.', 'basic', '{"required":true,"allowed":["basic","standard","strong"]}', 0, 1, 1, 0, 30),
('security.lockout_threshold', '5', 'integer', 'Security', 'Failed login attempts before account lockout.', '5', '{"required":true,"min":1,"max":20}', 0, 1, 1, 0, 40),
('security.password_expiry_days', '0', 'integer', 'Security', 'Password expiry interval; zero disables expiry.', '0', '{"required":true,"min":0,"max":3650}', 0, 1, 1, 0, 50),
('security.two_factor_enabled', '0', 'boolean', 'Security', 'Reserved two-factor authentication switch.', '0', '{"required":true}', 0, 0, 1, 0, 60),

('encounters.number_format', 'ENC-{YEAR}-{ID:6}', 'string', 'Encounters', 'Encounter number formatting template.', 'ENC-{YEAR}-{ID:6}', '{"required":true,"max_length":100}', 0, 1, 1, 0, 10),
('encounters.default_department_id', '', 'integer', 'Encounters', 'Optional default encounter department ID.', '', '{"min":1}', 0, 1, 0, 0, 20),
('encounters.queue_rules', '[]', 'array', 'Encounters', 'Encounter queue rule overrides.', '[]', '{"required":true}', 0, 1, 0, 0, 30),

('queue.auto_queue', '1', 'boolean', 'Queue', 'Automatically enqueue eligible encounters.', '1', '{"required":true}', 0, 1, 1, 0, 10),
('queue.prefix', 'Q', 'string', 'Queue', 'Default queue number prefix.', 'Q', '{"required":true,"max_length":20}', 1, 1, 0, 0, 20),
('queue.reset_rule', 'daily', 'string', 'Queue', 'Queue numbering reset frequency.', 'daily', '{"required":true,"allowed":["never","daily","weekly","monthly"]}', 0, 1, 0, 0, 30),

('notifications.email_enabled', '0', 'boolean', 'Notifications', 'Enable email notifications.', '0', '{"required":true}', 0, 1, 0, 0, 10),
('notifications.sms_enabled', '0', 'boolean', 'Notifications', 'Enable SMS notifications.', '0', '{"required":true}', 0, 1, 0, 0, 20),
('notifications.internal_enabled', '1', 'boolean', 'Notifications', 'Enable internal application notifications.', '1', '{"required":true}', 0, 1, 0, 0, 30),

('reporting.default_date_range_days', '30', 'integer', 'Reporting', 'Default reporting date range in days.', '30', '{"required":true,"min":1,"max":366}', 0, 1, 0, 0, 10),
('reporting.export_limit', '10000', 'integer', 'Reporting', 'Maximum rows in one report export.', '10000', '{"required":true,"min":100,"max":1000000}', 0, 1, 0, 0, 20),

('backup.frequency', 'daily', 'string', 'Backup', 'Requested backup frequency.', 'daily', '{"required":true,"allowed":["manual","daily","weekly","monthly"]}', 0, 1, 0, 0, 10),
('backup.retention_days', '30', 'integer', 'Backup', 'Requested backup retention in days.', '30', '{"required":true,"min":1,"max":3650}', 0, 1, 0, 0, 20),

('system.maintenance_mode', '0', 'boolean', 'System', 'Application maintenance mode switch.', '0', '{"required":true}', 1, 1, 1, 0, 10),
('system.debug_mode', '0', 'boolean', 'System', 'Application diagnostic mode switch.', '0', '{"required":true}', 0, 1, 1, 0, 20),
('system.version', '1.0.0', 'string', 'System', 'Displayed application version.', '1.0.0', '{"required":true,"regex":"^[0-9]+\\.[0-9]+\\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?$"}', 1, 0, 1, 0, 30);
