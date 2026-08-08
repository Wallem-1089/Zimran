<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/SettingsService.php';

$service = new SettingsService($pdo);
$actorId = 1;
$failures = [];

function assertSetting(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }
}

assertSetting(count($service->listGroups()) >= 10, 'Expected core and Medical Records settings groups.');
assertSetting($service->getString('hospital.code') === 'HMS', 'String retrieval failed.');
assertSetting($service->getInteger('security.session_timeout_minutes') === 30, 'Integer retrieval failed.');
assertSetting($service->getBoolean('queue.auto_queue') === true, 'Boolean retrieval failed.');
assertSetting($service->getArray('encounters.queue_rules') === [], 'Array retrieval failed.');
assertSetting(count($service->getGroup('Hospital')) === 7, 'Grouped retrieval failed.');
assertSetting(array_key_exists('hospital.name', $service->getPublicSettings()), 'Public settings retrieval failed.');
assertSetting(count($service->getSystemSettings()) > 0, 'System settings retrieval failed.');

$invalid = $service->update('security.session_timeout_minutes', 1, $actorId);
assertSetting(!$invalid['success'], 'Minimum validation did not reject an invalid timeout.');
assertSetting($service->getInteger('security.session_timeout_minutes') === 30, 'Invalid update changed stored data.');

$cacheUpdate = $service->update('security.session_timeout_minutes', 31, $actorId);
assertSetting($cacheUpdate['success'], 'Valid cache update failed.');
assertSetting($service->getInteger('security.session_timeout_minutes') === 31, 'Cache was not invalidated after update.');
assertSetting($service->reset('security.session_timeout_minutes', $actorId)['success'], 'Timeout reset failed.');

$bulk = $service->updateMany([
    'queue.prefix' => 'TESTQ',
    'reporting.default_date_range_days' => 45
], $actorId);
assertSetting($bulk['success'], 'Bulk update failed.');
assertSetting($service->getString('queue.prefix') === 'TESTQ', 'Bulk string value was not stored.');
assertSetting($service->getInteger('reporting.default_date_range_days') === 45, 'Bulk integer value was not stored.');
$service->reset('queue.prefix', $actorId);
$service->reset('reporting.default_date_range_days', $actorId);

$customKey = 'test.milestone_1_6';
if ($service->exists($customKey)) {
    $service->delete($customKey, $actorId);
}

$created = $service->set($customKey, 12, [
    'setting_type' => 'integer',
    'setting_group' => 'System',
    'description' => 'Milestone 1.6 automated test setting.',
    'default_value' => 10,
    'validation_rules' => ['required' => true, 'min' => 1, 'max' => 20],
    'is_editable' => true,
    'is_system' => false
], $actorId);
assertSetting($created['success'], 'Custom setting creation failed.');
assertSetting($service->exists($customKey), 'exists() did not find custom setting.');
assertSetting($service->getInteger($customKey) === 12, 'Custom typed retrieval failed.');
assertSetting($service->update($customKey, 13, $actorId)['success'], 'Custom setting update failed.');
assertSetting($service->delete($customKey, $actorId)['success'], 'Custom setting deletion failed.');
assertSetting(!$service->exists($customKey), 'Deleted setting remained in cache.');

$floatKey = 'test.float_setting';
if ($service->exists($floatKey)) {
    $service->delete($floatKey, $actorId);
}
$floatCreated = $service->set($floatKey, 1.25, [
    'setting_type' => 'float',
    'setting_group' => 'System',
    'default_value' => 1.0,
    'validation_rules' => ['min' => 0, 'max' => 2]
], $actorId);
assertSetting($floatCreated['success'], 'Float setting creation failed.');
assertSetting(abs($service->getFloat($floatKey) - 1.25) < 0.001, 'Float retrieval failed.');
assertSetting($service->delete($floatKey, $actorId)['success'], 'Float setting cleanup failed.');

$systemDelete = $service->delete('hospital.name', $actorId);
assertSetting(!$systemDelete['success'], 'System setting deletion was not prevented.');

$sensitiveKey = 'test.sensitive_setting';
if ($service->exists($sensitiveKey)) {
    $service->delete($sensitiveKey, $actorId);
}
$secret = 'do-not-expose-this-value';
$sensitive = $service->set($sensitiveKey, $secret, [
    'setting_type' => 'string',
    'setting_group' => 'Security',
    'description' => 'Sensitive audit redaction test.',
    'default_value' => '',
    'is_editable' => true,
    'is_sensitive' => true
], $actorId);
assertSetting($sensitive['success'], 'Sensitive setting creation failed.');
$auditStmt = $pdo->prepare('
    SELECT description FROM audit_logs
    WHERE action = :action ORDER BY id DESC LIMIT 1
');
$auditStmt->execute([':action' => 'SETTING_CREATED']);
assertSetting(!str_contains((string)$auditStmt->fetchColumn(), $secret), 'Sensitive value appeared in audit description.');
$export = $service->exportSettings('Security');
$exportedSensitive = array_values(array_filter(
    $export,
    static fn (array $setting): bool => $setting['setting_key'] === $sensitiveKey
));
assertSetting(($exportedSensitive[0]['setting_value'] ?? null) === '[REDACTED]', 'Sensitive export value was not redacted.');
assertSetting($service->delete($sensitiveKey, $actorId)['success'], 'Sensitive test setting deletion failed.');

$history = $service->getHistory($customKey, null, 1, 20);
assertSetting($history['success'] && $history['total'] >= 3, 'Setting history did not record CRUD actions.');

$auditCount = (int)$pdo->query(
    "SELECT COUNT(*) FROM audit_logs WHERE action IN (
        'SETTING_CREATED', 'SETTING_UPDATED', 'SETTING_DELETED', 'SETTING_RESET'
    )"
)->fetchColumn();
assertSetting($auditCount > 0, 'Settings audit events were not recorded.');

if ($failures !== []) {
    echo "FAILED\n" . implode("\n", $failures) . "\n";
    exit(1);
}

echo "PASS: settings CRUD, validation, bulk updates, cache invalidation, history, typed retrieval, and audit redaction.\n";
