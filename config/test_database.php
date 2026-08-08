<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/tools/DatabaseSafety.php';

DatabaseSafety::requireCli();
$config = require __DIR__ . '/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL
    . 'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$database = $config['database'];
$pdo = new PDO(
    'mysql:host=' . $database['host'] . ';dbname=' . $resolved['test']
        . ';charset=utf8mb4',
    $database['user'],
    $database['pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);
$GLOBALS['pdo'] = $pdo;
