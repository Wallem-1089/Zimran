# Settings Integration Guide

Future modules must read configurable values through `SettingsService`; controllers and views must not query `system_settings` directly.

```php
$settings = new SettingsService($pdo);
$timeout = $settings->getInteger('security.session_timeout_minutes', 30);
$autoQueue = $settings->getBoolean('queue.auto_queue', true);
$rules = $settings->getArray('encounters.queue_rules', []);
```

Planned consumers include Consultation, Laboratory, Radiology, Pharmacy, Accounts, Store, Physiotherapy, Theatre, Reporting, and Medical Records. Each module should add its keys through an additive migration, provide safe defaults, define validation rules, and retrieve typed values from the service.

Existing modules remain on their current configuration paths until a separately approved compatibility migration is performed.
