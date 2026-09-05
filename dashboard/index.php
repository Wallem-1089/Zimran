<?php

declare(strict_types=1);

$pageTitle = 'Dashboard';

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../services/PermissionService.php';

$currentDate = date('l, d F Y');
$permissionService = new PermissionService($pdo);
$isAdministrator = $permissionService->isAdministrator($currentUser);
$canRegisterPatient = $permissionService->canRegisterPatient($currentUser);
$canCreateEncounter = $permissionService->hasPermission('create_encounter', $currentUser);
$canViewReports = $permissionService->canViewReports($currentUser)
    || $permissionService->canViewClinicalReports($currentUser)
    || $permissionService->canViewFinancialReports($currentUser)
    || $permissionService->canViewInventoryReports($currentUser);
$activeDepartmentId = (int)($currentUser['active_department_id'] ?? $currentUser['department_id'] ?? 0);
$activeDepartmentName = trim((string)($currentUser['active_department_name'] ?? $currentUser['department_name'] ?? 'Department'));
$isStockRequestOnlyUser = !$isAdministrator
    && (
        (string)($currentUser['role_name'] ?? '') === 'Orderly'
        || $activeDepartmentName === 'Orderly'
    );
$isStockWorkflowDashboardUser = !$isAdministrator
    && (
        in_array((string)($currentUser['role_name'] ?? ''), ['Store Officer', 'Orderly'], true)
        || in_array($activeDepartmentName, ['Store', 'Orderly'], true)
    );

$todayPatients = (int)$pdo
    ->query('SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURDATE()')
    ->fetchColumn();

$activeEncounterCountSql = "SELECT COUNT(*) FROM visits WHERE visit_status NOT IN ('Completed', 'Cancelled')";
$activeEncounterCountParams = [];
if (!$isAdministrator) {
    $activeEncounterCountSql .= ' AND current_department_id = :department_id';
    $activeEncounterCountParams[':department_id'] = $activeDepartmentId;
}
$activeEncounterCountStmt = $pdo->prepare($activeEncounterCountSql);
$activeEncounterCountStmt->execute($activeEncounterCountParams);
$activeEncounters = (int)$activeEncounterCountStmt->fetchColumn();

$pendingDepartmentEncounters = 0;

if ($activeDepartmentId > 0) {
    $pendingDepartmentStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM visits
        WHERE current_department_id = :department_id
          AND visit_status NOT IN ('Completed', 'Cancelled')
    ");
    $pendingDepartmentStmt->execute([':department_id' => $activeDepartmentId]);
    $pendingDepartmentEncounters = (int)$pendingDepartmentStmt->fetchColumn();
}

$pendingBills = '0.00';
if ((int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'invoices'")->fetchColumn() > 0) {
    $pendingBillsStmt = $pdo->prepare("
        SELECT COALESCE(SUM(i.balance_due), 0)
        FROM invoices i
        INNER JOIN visits v ON v.id = i.visit_id
        WHERE i.status IN ('Unpaid', 'Partially Paid')
          AND v.current_department_id = :department_id
    ");
    $pendingBillsStmt->execute([':department_id' => $activeDepartmentId]);
    $pendingBills = number_format((float)$pendingBillsStmt->fetchColumn(), 2);
}

$stockRequestsReady = (int)$pdo
    ->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'stock_requests'")
    ->fetchColumn() > 0;
$pendingStockRequests = 0;
$departmentStockItems = 0;

if ($stockRequestsReady && $isStockWorkflowDashboardUser) {
    $pendingStockSql = "
        SELECT COUNT(*)
        FROM stock_requests
        WHERE status = 'Pending'
    ";
    $pendingStockParams = [];
    if (strcasecmp($activeDepartmentName, 'Store') !== 0 && !$permissionService->canIssueStockRequests($currentUser)) {
        $pendingStockSql .= ' AND requesting_department_id = :department_id';
        $pendingStockParams[':department_id'] = $activeDepartmentId;
    }
    $pendingStockStmt = $pdo->prepare($pendingStockSql);
    $pendingStockStmt->execute($pendingStockParams);
    $pendingStockRequests = (int)$pendingStockStmt->fetchColumn();
}

if ($activeDepartmentId > 0
    && (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'department_stock_balances'")->fetchColumn() > 0
) {
    $departmentStockStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM department_stock_balances
        WHERE department_id = :department_id
          AND quantity > 0
    ");
    $departmentStockStmt->execute([':department_id' => $activeDepartmentId]);
    $departmentStockItems = (int)$departmentStockStmt->fetchColumn();
}

$activeEncounterSql = "
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
";
$activeEncounterParams = [];
if (!$isAdministrator) {
    $activeEncounterSql .= ' AND v.current_department_id = :department_id';
    $activeEncounterParams[':department_id'] = $activeDepartmentId;
}
$activeEncounterSql .= "
    ORDER BY v.visit_date DESC, v.id DESC
    LIMIT 25
";
$activeEncounterStmt = $pdo->prepare($activeEncounterSql);
$activeEncounterStmt->execute($activeEncounterParams);
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

        <?php if ($isStockWorkflowDashboardUser): ?>
            <div class="card">
                <h3>Pending Stock Requests</h3>
                <h2><?= (int)$pendingStockRequests ?></h2>
            </div>

            <div class="card">
                <h3><?= e($activeDepartmentName) ?> Stock Items</h3>
                <h2><?= (int)$departmentStockItems ?></h2>
            </div>
        <?php else: ?>

        <div class="card">
            <h3><?= $isAdministrator ? 'Active Encounters' : e($activeDepartmentName) . ' Active Encounters' ?></h3>
            <h2><?= (int)$activeEncounters ?></h2>
        </div>

        <div class="card">
            <h3><?= e($activeDepartmentName) ?> Pending Encounters</h3>
            <h2><?= (int)$pendingDepartmentEncounters ?></h2>
        </div>

        <div class="card">
            <h3><?= e($activeDepartmentName) ?> Pending Bills</h3>
            <h2>₦<?= e($pendingBills) ?></h2>
        </div>
        <?php endif; ?>
    </section>

    <section class="quick-actions">
        <h2>Quick Actions</h2>

        <div class="actions">
            <?php if ($isStockWorkflowDashboardUser): ?>
                <a href="../modules/stock_requests/index.php">Stock Requests</a>
                <a href="../modules/stock_requests/create.php">New Stock Request</a>
                <a href="../modules/stock_requests/my_department_stock.php">My Department Stock</a>
                <?php if ($permissionService->canViewInventory($currentUser)): ?>
                    <a href="../modules/store/index.php">Store Inventory</a>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($canRegisterPatient): ?>
                    <a href="../modules/patients/register.php">Register Patient</a>
                <?php endif; ?>
                <?php if ($canCreateEncounter): ?>
                    <a href="../modules/visits/create.php">New Encounter</a>
                <?php endif; ?>
                <a href="../modules/patients/search.php">Find Encounter</a>
                <?php if ($canViewReports): ?>
                    <a href="../modules/reports/index.php">Reports</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!$isStockWorkflowDashboardUser): ?>

    <section class="card">
        <h2>Current Working Encounters</h2>
        <p class="text-muted">
            <?= $isAdministrator
                ? 'Active patient encounters that are not completed or cancelled.'
                : e($activeDepartmentName) . ' active patient encounters that are not completed or cancelled.' ?>
        </p>

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

    <?php endif; ?>

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
                <?php foreach (['Reception', 'Records', 'Doctors', 'Nursing', 'Laboratory', 'X-Ray / Radiology', 'ECG', 'POP', 'Physiotherapy', 'Theatre', 'Pharmacy', 'Accounts', 'Store', 'Orderly'] as $department): ?>
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
