<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/tools/DatabaseSafety.php';

DatabaseSafety::requireCli();
$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
fwrite(STDOUT, 'Resolved live database: ' . $resolved['live'] . PHP_EOL);
fwrite(STDOUT, 'Resolved test database: ' . $resolved['test'] . PHP_EOL);
DatabaseSafety::requireDestructiveApproval($argv);

$database = $config['database'];
$pdo = new PDO(
    'mysql:host=' . $database['host'] . ';dbname=' . $resolved['test'] . ';charset=utf8mb4',
    $database['user'],
    $database['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_MULTI_STATEMENTS => true]
);
if ((string)$pdo->query('SELECT DATABASE()')->fetchColumn() !== $resolved['test']) {
    throw new RuntimeException('Migration cycle target is not the resolved test database.');
}

foreach (['patient_problems', 'patient_problem_history', 'patient_medical_history', 'patient_medical_history_versions'] as $table) {
    if ((int)$pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() !== 0) {
        throw new RuntimeException('Migration cycle requires empty Milestone 2.4 tables.');
    }
}

$upPath = __DIR__ . '/../database/migrations/019_phase2_problem_list_medical_history_up.sql';
$downPath = __DIR__ . '/../database/migrations/019_phase2_problem_list_medical_history_down.sql';
$up = (string)file_get_contents($upPath);
$down = (string)file_get_contents($downPath);
DatabaseSafety::assertSafeSchema($up, $resolved['live']);
DatabaseSafety::assertSafeSchema($down, $resolved['live']);
DatabaseSafety::logOperation(
    $resolved['test'],
    'Explicit Migration 019 down/up verification on empty dedicated test tables',
    getenv('HMS_VERIFIED_BACKUP_PATH') !== false ? 'verified-backup' : 'explicit-test-acknowledgement'
);

$pdo->exec($down);
foreach (['patient_problems', 'patient_problem_history', 'patient_medical_history', 'patient_medical_history_versions'] as $table) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=:schema AND table_name=:table');
    $stmt->execute([':schema' => $resolved['test'], ':table' => $table]);
    if ((int)$stmt->fetchColumn() !== 0) {
        throw new RuntimeException('Down migration did not remove ' . $table . '.');
    }
}
$pdo->exec($up);
foreach (['patient_problems', 'patient_problem_history', 'patient_medical_history', 'patient_medical_history_versions'] as $table) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=:schema AND table_name=:table');
    $stmt->execute([':schema' => $resolved['test'], ':table' => $table]);
    if ((int)$stmt->fetchColumn() !== 1) {
        throw new RuntimeException('Up migration did not restore ' . $table . '.');
    }
}
$ledger = $pdo->prepare("SELECT checksum FROM schema_migrations WHERE migration_name='019_phase2_problem_list_medical_history_up.sql'");
$ledger->execute();
if (!hash_equals((string)$ledger->fetchColumn(), (string)hash_file('sha256', $upPath))) {
    throw new RuntimeException('Migration 019 checksum does not match the ledger.');
}

fwrite(STDOUT, 'Migration 019 isolated down/up verification passed.' . PHP_EOL);
