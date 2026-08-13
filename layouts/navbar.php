<?php

declare(strict_types=1);

$currentDateTime = date('l, d F Y - h:i A');
$currentUser ??= [];
$config = require __DIR__ . '/../config/app.php';
$baseUrl = rtrim($config['app']['base_url'], '/');

?>

<header class="navbar">

    <div class="navbar-left">

        <h1>

            <?= e($pageTitle ?? 'Hospital Management System') ?>

        </h1>

        <span>

            <?= e($currentDateTime) ?>

        </span>

    </div>

    <div class="navbar-right">

        <div class="navbar-user">

            <div class="user-details">

                <strong>

                    <?= e($currentUser['first_name'] ?? '') ?>

                    <?= e($currentUser['last_name'] ?? '') ?>

                </strong>

                <small>

                    <?= e($currentUser['role_name'] ?? '') ?>

                    -

                    <?= e($currentUser['active_department_name'] ?? $currentUser['department_name'] ?? '') ?>

                </small>

            </div>

        </div>

        <div class="navbar-actions">

            <a
                href="<?= e($baseUrl) ?>/authentication/logout.php"
                class="logout-link">

                Logout

            </a>

        </div>

    </div>

</header>
