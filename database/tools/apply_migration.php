<?php

declare(strict_types=1);

require_once __DIR__ . '/MigrationManager.php';

DatabaseSafety::requireCli();

$migration = $argv[1] ?? '';
$batch = isset($argv[2]) ? (int)$argv[2] : 1;

if ($migration === '') {
    throw new RuntimeException('Usage: php database/tools/apply_migration.php <migration_file> [batch]');
}

$path = dirname(__DIR__) . '/migrations/' . basename($migration);
if (!is_file($path)) {
    throw new RuntimeException('Migration file not found: ' . basename($migration));
}

try {
    $config = require dirname(__DIR__, 2) . '/config/app.php';
    $database = $config['database'];

    $pdo = new PDO(
        'mysql:host=' . $database['host'] . ';dbname=' . $database['name'] . ';charset=utf8mb4',
        $database['user'],
        $database['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $manager = new MigrationManager($pdo, (string)$database['name']);
    $manager->ensureLedger();
    $manager->apply($path, $batch);

    fwrite(STDOUT, 'Applied ' . basename($migration) . PHP_EOL);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
