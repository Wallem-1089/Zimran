<?php

declare(strict_types=1);

$pageTitle = 'Dashboard';

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$currentDate = date('l, d F Y');

$todayPatients = (int)$pdo
    ->query('SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURDATE()')
    ->fetchColumn();

$activeEncounters = (int)$pdo
    ->query("SELECT COUNT(*) FROM visits WHERE visit_status NOT IN ('Completed', 'Cancelled')")
    ->fetchColumn();

$pendingLaboratory = 0;
if ((int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'laboratory_requests'")->fetchColumn() > 0) {
    $pendingLaboratory = (int)$pdo
        ->query("SELECT COUNT(*) FROM laboratory_requests WHERE status IN ('Requested', 'In Progress')")
        ->fetchColumn();
}

$pendingBills = '0.00';
if ((int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'invoices'")->fetchColumn() > 0) {
    $pendingBills = number_format((float)$pdo
        ->query("SELECT COALESCE(SUM(balance_due), 0) FROM invoices WHERE status IN ('Unpaid', 'Partially Paid')")
        ->fetchColumn(), 2);
}

$activeEncounterStmt = $pdo->prepare("
    SELECT
        v.id,
        v.visit_number,
        v.visit_status,
        v.visit_date,
        p.hospital_number,
        CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
        d.department_name,
        CONCAT(u.first_name, ' ', u.last_name) AS doctor_name
    FROM visits v
    INNER JOIN patients p ON p.id = v.patient_id
    LEFT JOIN departments d ON d.id = v.current_department_id
    LEFT JOIN users u ON u.id = v.attending_doctor_id
    WHERE v.visit_status NOT IN ('Completed', 'Cancelled')
    ORDER BY v.visit_date DESC, v.id DESC
    LIMIT 25
");
$activeEncounterStmt->execute();
$currentWorkingEncounters = $activeEncounterStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="content">

<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p><?= e($currentDate) ?></p>
        </div>
    </div>

    <section class="stats">
        <div class="card">
            <h3>Today's Patients</h3>
            <h2><?= (int)$todayPatients ?></h2>
        </div>

        <div class="card">
            <h3>Active Encounters</h3>
            <h2><?= (int)$activeEncounters ?></h2>
        </div>

        <div class="card">
            <h3>Pending Laboratory</h3>
            <h2><?= (int)$pendingLaboratory ?></h2>
        </div>

        <div class="card">
            <h3>Pending Bills</h3>
            <h2>₦<?= e($pendingBills) ?></h2>
        </div>
    </section>

    <section class="quick-actions">
        <h2>Quick Actions</h2>

        <div class="actions">
            <a href="../modules/patients/register.php">Register Patient</a>
            <a href="../modules/visits/create.php">New Encounter</a>
            <a href="../modules/patients/search.php">Find Encounter</a>
            <a href="../modules/reports/index.php">Reports</a>
        </div>
    </section>

    <section class="card">
        <h2>Current Working Encounters</h2>
        <p class="text-muted">Active patient encounters that are not completed or cancelled.</p>

        <?php if ($currentWorkingEncounters === []): ?>
            <div class="empty-state">No active encounters at the moment.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Hospital Number</th>
                            <th>Visit Number</th>
                            <th>Status</th>
                            <th>Department</th>
                            <th>Doctor</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currentWorkingEncounters as $encounter): ?>
                            <tr>
                                <td><?= e((string)($encounter['patient_name'] ?? '-')) ?></td>
                                <td><?= e((string)($encounter['hospital_number'] ?? '-')) ?></td>
                                <td><?= e((string)($encounter['visit_number'] ?? ('#' . (int)$encounter['id']))) ?></td>
                                <td><?= e((string)($encounter['visit_status'] ?? '-')) ?></td>
                                <td><?= e((string)($encounter['department_name'] ?? '-')) ?></td>
                                <td><?= e((string)($encounter['doctor_name'] ?? 'Not Assigned')) ?></td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="../modules/visits/workspace.php?id=<?= (int)$encounter['id'] ?>">Open Workspace</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

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
                <?php foreach (['Reception', 'Records', 'Doctors', 'Nursing', 'Laboratory', 'Radiology', 'Pharmacy', 'Accounts', 'Theatre', 'Store'] as $department): ?>
                    <tr>
                        <td><?= e($department) ?></td>
                        <td>Ready</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
