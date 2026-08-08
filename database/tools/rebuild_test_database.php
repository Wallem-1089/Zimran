<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseSafety.php';
require_once __DIR__ . '/MigrationManager.php';

DatabaseSafety::requireCli();
$config = require dirname(__DIR__, 2) . '/config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);

fwrite(STDOUT, 'Resolved live database: ' . $resolved['live'] . PHP_EOL);
fwrite(STDOUT, 'Resolved test database: ' . $resolved['test'] . PHP_EOL);

DatabaseSafety::requireDestructiveApproval($argv);
$schemaPath = dirname(__DIR__) . '/schema.sql';
$schema = (string)file_get_contents($schemaPath);
DatabaseSafety::assertSafeSchema($schema, $resolved['live']);

$database = $config['database'];
$server = new PDO(
    'mysql:host=' . $database['host'] . ';charset=utf8mb4',
    $database['user'],
    $database['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$quoted = DatabaseSafety::quoteIdentifier($resolved['test']);
$authorization = getenv('HMS_VERIFIED_BACKUP_PATH') !== false
    ? 'verified-backup'
    : 'explicit-no-test-backup-acknowledgement';
DatabaseSafety::logOperation(
    $resolved['test'],
    'Explicit reconstruction of dedicated automated test database',
    $authorization
);

$server->exec('DROP DATABASE IF EXISTS ' . $quoted);
$server->exec(
    'CREATE DATABASE ' . $quoted
    . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
);

$pdo = new PDO(
    'mysql:host=' . $database['host'] . ';dbname=' . $resolved['test']
        . ';charset=utf8mb4',
    $database['user'],
    $database['pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
    ]
);
$pdo->exec($schema);

$manager = new MigrationManager($pdo, $resolved['test']);
$manager->ensureLedger();
$migrationDirectory = dirname(__DIR__) . '/migrations';
$representedByBaseline = ['002', '003', '004', '013', '014'];
$paths = glob($migrationDirectory . '/*_up.sql') ?: [];
sort($paths, SORT_NATURAL);

foreach ($paths as $path) {
    $prefix = substr(basename($path), 0, 3);
    if (in_array($prefix, $representedByBaseline, true)) {
        $manager->recordRepresented($path, 1);
        continue;
    }
    $manager->apply($path, 1);
}

fwrite(STDOUT, 'Dedicated test database reconstructed successfully.' . PHP_EOL);
