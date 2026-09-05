<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseSafety.php';
require_once __DIR__ . '/MigrationManager.php';

DatabaseSafety::requireCli();
try {
    $config = require dirname(__DIR__, 2) . '/config/app.php';
    $database = $config['database'];

    if (in_array('--test', $argv, true)) {
        $resolved = DatabaseSafety::resolveTestDatabase($config);
        $database['name'] = $resolved['test'];
        fwrite(STDOUT, 'Resolved live database: ' . $resolved['live'] . PHP_EOL);
        fwrite(STDOUT, 'Resolved test database: ' . $resolved['test'] . PHP_EOL);
    } elseif (in_array('--live', $argv, true)) {
        fwrite(STDOUT, 'Resolved live database: ' . $database['name'] . PHP_EOL);
    } else {
        throw new RuntimeException('Specify --live or --test explicitly. Usage: php database/tools/migration_status.php --live|--test');
    }

    $pdo = new PDO(
        'mysql:host=' . $database['host'] . ';dbname=' . $database['name']
            . ';charset=utf8mb4',
        $database['user'],
        $database['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $paths = glob(dirname(__DIR__) . '/migrations/*_up.sql') ?: [];
    sort($paths, SORT_NATURAL);
    $manager = new MigrationManager($pdo, (string)$database['name']);
    foreach ($manager->status($paths) as $row) {
        fwrite(
            STDOUT,
            sprintf(
                "%s\t%s\tbatch=%s\tapplied=%s%s",
                $row['status'],
                $row['migration'],
                $row['batch'] ?? '-',
                $row['applied_at'] ?? '-',
                PHP_EOL
            )
        );
    }
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Migration status failed: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
