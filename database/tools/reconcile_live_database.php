<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseSafety.php';
require_once __DIR__ . '/MigrationManager.php';

DatabaseSafety::requireCli();
if (!in_array('--confirm-live-reconstruction', $argv, true)) {
    throw new RuntimeException('Pass --confirm-live-reconstruction to continue.');
}
$backupPath = trim((string)getenv('HMS_VERIFIED_BACKUP_PATH'));
if ($backupPath === '') {
    throw new RuntimeException('HMS_VERIFIED_BACKUP_PATH is required.');
}
$backup = DatabaseSafety::verifyBackup($backupPath);
$config = require dirname(__DIR__, 2) . '/config/app.php';
$database = $config['database'];
$name = trim((string)$database['name']);
fwrite(STDOUT, 'Resolved live database: ' . $name . PHP_EOL);
fwrite(STDOUT, 'Verified backup: ' . $backup['path'] . PHP_EOL);

$pdo = new PDO(
    'mysql:host=' . $database['host'] . ';dbname=' . $name . ';charset=utf8mb4',
    $database['user'],
    $database['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$manager = new MigrationManager($pdo, $name);
$manager->ensureLedger();
$directory = dirname(__DIR__) . '/migrations';
$paths = glob($directory . '/*_up.sql') ?: [];
sort($paths, SORT_NATURAL);

$represented = [
    '002' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'encounter_events'",
    '003' => "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'visit_queue' AND column_name = 'assigned_user_id'",
    '004' => "SELECT LOCATE('Store', column_type) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'visits' AND column_name = 'visit_status'",
    '005' => "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'locked_at'",
    '006' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'role_permissions'",
    '007' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'user_departments'",
    '008' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'active_sessions'",
    '009' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'system_settings'",
    '010' => "SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'audit_logs' AND index_name = 'idx_audit_module_created'",
    '011' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'phase1_visit_status_repair'",
    '012' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'phase1_patient_gender_repair'",
    '013' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'patient_demographic_history'",
    '014' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'patient_identifiers'"
];

foreach ($paths as $path) {
    $prefix = substr(basename($path), 0, 3);
    if ($prefix === '015') {
        continue;
    }
    $query = $represented[$prefix] ?? null;
    if ($query !== null && (int)$pdo->query($query)->fetchColumn() > 0) {
        $manager->recordRepresented($path, 1);
        continue;
    }
    $manager->apply($path, 1);
}

$migration015 = $directory . '/015_recovery_safety_and_seed_reconciliation_up.sql';
$manager->apply($migration015, 2);
DatabaseSafety::logOperation(
    $name,
    'Additive controlled live development database reconstruction',
    'verified-backup:' . basename($backup['path'])
);
fwrite(STDOUT, 'Live development schema reconciliation completed.' . PHP_EOL);
