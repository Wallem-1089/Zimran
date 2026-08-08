<?php

$config = require __DIR__ . '/app.php';

$db = $config['database'];

if (($config['app']['environment'] ?? 'production') === 'testing') {
    throw new RuntimeException(
        'Live database bootstrap is disabled in testing. Use config/test_database.php.'
    );
}

try {

    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
        $db['user'],
        $db['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $GLOBALS['pdo'] = $pdo;

} catch (PDOException $e) {

    error_log($e->getMessage());

    die("Unable to connect to the database.");

}
