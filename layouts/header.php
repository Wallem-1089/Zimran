<?php

declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = 'Hospital Management System';
}

require_once __DIR__ . '/../config/helpers.php';

$config = require __DIR__ . '/../config/app.php';

$baseUrl = rtrim(
    $config['app']['base_url'],
    '/'
);

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
        content="Hospital Management System">

    <meta
        name="author"
        content="Hospital Management System">

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge">

    <title><?= e($pageTitle) ?></title>

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