<?php

declare(strict_types=1);

if (!isset($currentUser)) {
    $currentUser = null;
}

$currentPage = basename($_SERVER['PHP_SELF']);

?>

<!-- Sidebar -->

<aside class="sidebar">

    <div class="sidebar-header">

        <h2>HMS</h2>

        <p>Hospital Management System</p>

    </div>

    <?php if ($currentUser): ?>

        <div class="sidebar-user">

            <strong>

                <?= e($currentUser['first_name']) ?>

                <?= e($currentUser['last_name']) ?>

            </strong>

            <small>

                <?= e($currentUser['role_name']) ?>

            </small>

        </div>

    <?php endif; ?>

    <nav class="sidebar-nav">

        <ul>

            <li>

                <a
                    href="<?= e($baseUrl) ?>/dashboard/index.php"
                    class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">

                    Dashboard

                </a>

            </li>

            <li>

                <a href="<?= e($baseUrl) ?>/modules/patients/search.php">

                    Patients

                </a>

            </li>

            <li>

                <a href="<?= e($baseUrl) ?>/modules/patients/search.php">

                    Encounters

                </a>

            </li>

        </ul>

    </nav>

    <div class="sidebar-footer">

        <a
            href="<?= e($baseUrl) ?>/authentication/logout.php"
            class="logout-btn">

            Logout

        </a>

    </div>

</aside>
<div class="main-container">
