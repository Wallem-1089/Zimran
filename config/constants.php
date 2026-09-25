<?php

define("APP_NAME","Zimran E-HMIS");

define("APP_VERSION","1.1");

$appConfig = require __DIR__ . '/app.php';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim((string)($appConfig['app']['base_url'] ?? '/'), '/');

define("BASE_URL", $scheme . '://' . $host . $basePath);

date_default_timezone_set("Africa/Lagos");

?>
