<?php

declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = 'E-HMIS';
}

require_once __DIR__ . '/../config/helpers.php';

$config = require __DIR__ . '/../config/app.php';

$baseUrl = rtrim(
    $config['app']['base_url'],
    '/'
);

$branding = appBranding($GLOBALS['pdo'] ?? null);
$appMetaName = $branding['full_name'];

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="<?= e($appMetaName) ?>">

    <meta
        name="author"
        content="<?= e($branding['display_name']) ?>">

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge">

    <title><?= e($pageTitle . ' | ' . $appMetaName) ?></title>

    <!-- Global Stylesheet -->

    <link
        rel="stylesheet"
        href="<?= e($baseUrl) ?>/assets/css/dashboard.css">

    <!-- Module Stylesheet -->

    <?php if (!empty($moduleStylesheet)): ?>

    <link
        rel="stylesheet"
        href="<?= e($baseUrl . $moduleStylesheet) ?>">

    <?php endif; ?>

    <!--
    -----------------------------------------------------------------------
    Future Local Assets

    Uncomment these after downloading them into your project.

    <link
        rel="stylesheet"
        href="<?= e($baseUrl) ?>/assets/fontawesome/css/all.min.css">

    <link
        rel="stylesheet"
        href="<?= e($baseUrl) ?>/assets/css/theme.css">
    -----------------------------------------------------------------------
    -->

</head>

<body>

<div class="wrapper">
