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
                    href="../../dashboard/index.php"
                    class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">

                    Dashboard

                </a>

            </li>

            <li>

                <a href="../modules/patients/search.php">

                    Patients

                </a>

            </li>

            <li>

                <a href="../visits/">

                    Encounters

                </a>

            </li>

            <li>

                <a href="../workspace/">

                    Workspace

                </a>

            </li>

            <li>

                <a href="../appointments/">

                    Appointments

                </a>

            </li>

            <li>

                <a href="../laboratory/">

                    Laboratory

                </a>

            </li>

            <li>

                <a href="../radiology/">

                    X-Ray

                </a>

            </li>

            <li>

                <a href="../pharmacy/">

                    Pharmacy

                </a>

            </li>

            <li>

                <a href="../accounts/">

                    Accounts

                </a>

            </li>

            <li>

                <a href="../theatre/">

                    Theatre

                </a>

            </li>

            <li>

                <a href="../store/">

                    Store

                </a>

            </li>

            <li>

                <a href="../reports/">

                    Reports

                </a>

            </li>

            <?php if (
                isset($currentUser['role_name']) &&
                $currentUser['role_name'] === 'System Administrator'
            ): ?>

                <li>

                    <a href="../admin/">

                        Administration

                    </a>

                </li>

            <?php endif; ?>

        </ul>

    </nav>

    <div class="sidebar-footer">

        <a
            href="../authentication/logout.php"
            class="logout-btn">

            Logout

        </a>

    </div>

</aside>
<div class="main-container">