<?php

declare(strict_types=1);

$pageTitle = 'Dashboard';

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$currentDate = date('l, d F Y');

?>

<!-- Main Content -->

<main class="content">

<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <!-- Statistics -->

    <section class="stats">

        <div class="card">

            <h3>Today's Patients</h3>

            <h2>0</h2>

        </div>

        <div class="card">

            <h3>Active Encounters</h3>

            <h2>0</h2>

        </div>

        <div class="card">

            <h3>Pending Laboratory</h3>

            <h2>0</h2>

        </div>

        <div class="card">

            <h3>Pending Bills</h3>

            <h2>₦0.00</h2>

        </div>

    </section>

    <!-- Quick Actions -->

    <section class="quick-actions">

        <h2>Quick Actions</h2>

        <div class="actions">

            <a href="../modules/patients/register.php">

                Register Patient

            </a>

            <a href="../modules/visits/create.php">

                New Encounter

            </a>

            <a href="../modules/patients/search.php">

                Find Encounter

            </a>

            <a href="../reports/">

                Reports

            </a>

        </div>

    </section>

    <!-- Department Status -->

    <section class="departments">

        <h2>Hospital Departments</h2>

        <table>

            <thead>

                <tr>

                    <th>Department</th>

                    <th>Status</th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td>Reception</td>

                    <td>Ready</td>

                </tr>

                <tr>

                    <td>Records</td>

                    <td>Ready</td>

                </tr>

                <tr>

                    <td>Doctors</td>

                    <td>Ready</td>

                </tr>

                <tr>

                    <td>Nursing</td>

                    <td>Ready</td>

                </tr>

                <tr>

                    <td>Laboratory</td>

                    <td>Ready</td>

                </tr>

                <tr>

                    <td>Radiology</td>

                    <td>Ready</td>

                </tr>

                <tr>

                    <td>Pharmacy</td>

                    <td>Ready</td>

                </tr>

                <tr>

                    <td>Accounts</td>

                    <td>Ready</td>

                </tr>

                <tr>

                    <td>Theatre</td>

                    <td>Ready</td>

                </tr>

                <tr>

                    <td>Store</td>

                    <td>Ready</td>

                </tr>

            </tbody>

        </table>

    </section>

    <!-- Recent Activity -->

    <section class="activity">

        <h2>Recent Activity</h2>

        <p>

            No recent activity available.

        </p>

    </section>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
