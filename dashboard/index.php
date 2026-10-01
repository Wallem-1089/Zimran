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
$canViewBillingRequestQueues = $permissionService->canViewBillingRequests($currentUser)
    && (
        $isAdministrator
        || strcasecmp($activeDepartmentName, 'Accounts') === 0
        || in_array((string)($currentUser['role_name'] ?? ''), ['Accounts', 'Accountant'], true)
    );

function dashboardTableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = :table
    ');
    $stmt->execute([':table' => $table]);
    return (int)$stmt->fetchColumn() > 0;
}

function dashboardPendingClinicalBillingCount(PDO $pdo, string $sourceModule): int
{
    if (!dashboardTableExists($pdo, 'billing_requests')) {
        return 0;
    }

    if (strcasecmp($sourceModule, 'Plaster') === 0) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM billing_requests WHERE status = 'Pending' AND source_module IN ('Plaster', 'POP')");
        return (int)$stmt->fetchColumn();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM billing_requests WHERE status = 'Pending' AND source_module = :source_module");
    $stmt->execute([':source_module' => $sourceModule]);
    return (int)$stmt->fetchColumn();
}

function dashboardPendingRegistrationBillingCount(PDO $pdo, string $billingType, ?string $registrationType = null): int
{
    if (!dashboardTableExists($pdo, 'patient_registration_billing_requests')) {
        return 0;
    }

    $where = ['status = \'Pending\'', 'billing_type = :billing_type'];
    $params = [':billing_type' => $billingType];
    if ($registrationType !== null) {
        $where[] = 'registration_type = :registration_type';
        $params[':registration_type'] = $registrationType;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM patient_registration_billing_requests WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function dashboardInvoiceStatusCount(PDO $pdo, string $status): int
{
    if (!dashboardTableExists($pdo, 'invoices')) {
        return 0;
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM invoices WHERE status = :status');
    $stmt->execute([':status' => $status]);
    return (int)$stmt->fetchColumn();
}

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
    if (strcasecmp($activeDepartmentName, 'Store') !== 0 && !$permissionService->canIssueStockRequest($currentUser)) {
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

$dashboardDepartmentBillingQueues = [
    ['label' => 'Laboratory', 'source_module' => 'Laboratory'],
    ['label' => 'Radiology / X-Ray', 'source_module' => 'Radiology'],
    ['label' => 'ECG', 'source_module' => 'ECG'],
    ['label' => 'Plaster', 'source_module' => 'Plaster'],
    ['label' => 'Physiotherapy', 'source_module' => 'Physiotherapy'],
    ['label' => 'Pharmacy', 'source_module' => 'Pharmacy'],
    ['label' => 'Theatre', 'source_module' => 'Theatre'],
    ['label' => 'Patient Stock Usage', 'source_module' => 'Patient Stock Usage'],
    ['label' => 'Admission', 'source_module' => 'Admission'],
    ['label' => 'Nursing', 'source_module' => 'Nursing'],
    ['label' => 'Dressing', 'source_module' => 'Dressing'],
];

foreach ($dashboardDepartmentBillingQueues as $index => $queue) {
    $dashboardDepartmentBillingQueues[$index]['pending_count'] = dashboardPendingClinicalBillingCount($pdo, (string)$queue['source_module']);
    $dashboardDepartmentBillingQueues[$index]['href'] = '../modules/billing/billing_requests.php?' . http_build_query([
        'status' => 'Pending',
        'source_module' => $queue['source_module'],
    ]);
}

$dashboardPriorityBillingQueues = [
    [
        'label' => 'Patient Registration',
        'description' => 'Normal registration payments',
        'pending_count' => dashboardPendingRegistrationBillingCount($pdo, 'InitialRegistration', 'Normal'),
        'href' => '../modules/billing/registration_requests.php?' . http_build_query([
            'status' => 'Pending',
            'billing_type' => 'InitialRegistration',
            'registration_type' => 'Normal',
        ]),
    ],
    [
        'label' => 'Consultation Fee',
        'description' => 'Consultation billing requests',
        'pending_count' => dashboardPendingClinicalBillingCount($pdo, 'Consultation'),
        'href' => '../modules/billing/billing_requests.php?' . http_build_query([
            'status' => 'Pending',
            'source_module' => 'Consultation',
        ]),
    ],
    [
        'label' => 'Patient Renewal',
        'description' => 'Monthly renewal payments',
        'pending_count' => dashboardPendingRegistrationBillingCount($pdo, 'MonthlyRenewal'),
        'href' => '../modules/billing/registration_requests.php?' . http_build_query([
            'status' => 'Pending',
            'billing_type' => 'MonthlyRenewal',
        ]),
    ],
    [
        'label' => 'Emergency Registration',
        'description' => 'Emergency registration payments',
        'pending_count' => dashboardPendingRegistrationBillingCount($pdo, 'InitialRegistration', 'Emergency'),
        'href' => '../modules/billing/registration_requests.php?' . http_build_query([
            'status' => 'Pending',
            'billing_type' => 'InitialRegistration',
            'registration_type' => 'Emergency',
        ]),
    ],
];

$dashboardPaymentStatusQueues = [
    [
        'label' => 'Unpaid Encounters',
        'description' => 'Invoices with no payment yet',
        'pending_count' => dashboardInvoiceStatusCount($pdo, 'Unpaid'),
        'href' => '../modules/billing/index.php?' . http_build_query(['status' => 'Unpaid']),
    ],
    [
        'label' => 'Partially Paid Encounters',
        'description' => 'Invoices with outstanding balances',
        'pending_count' => dashboardInvoiceStatusCount($pdo, 'Partially Paid'),
        'href' => '../modules/billing/index.php?' . http_build_query(['status' => 'Partially Paid']),
    ],
];

$dashboardRegistrationBillingQueues = [];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="content">
    <style>
        .dashboard-queue-grid .dashboard-queue-button {
            color: inherit;
            display: block;
            min-height: 112px;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .dashboard-queue-grid .dashboard-queue-button:hover,
        .dashboard-queue-grid .dashboard-queue-button:focus {
            border-color: #2563eb;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.12);
            transform: translateY(-1px);
        }

        .dashboard-queue-grid .queue-hint {
            display: block;
            margin-top: 0.35rem;
            font-size: 0.82rem;
        }
    </style>

<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p><?= e($currentDate) ?></p>
        </div>
    </div>

    <section class="stats dashboard-queue-grid">
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

        <a class="card dashboard-queue-button" href="../modules/visits/department_worklist.php">
            <h3><?= $isAdministrator ? 'Active Encounters' : e($activeDepartmentName) . ' Active Encounters' ?></h3>
            <h2><?= (int)$activeEncounters ?></h2>
            <span class="text-muted queue-hint">Open encounter worklist</span>
        </a>

        <a class="card dashboard-queue-button" href="../modules/visits/department_worklist.php">
            <h3><?= e($activeDepartmentName) ?> Pending Encounters</h3>
            <h2><?= (int)$pendingDepartmentEncounters ?></h2>
            <span class="text-muted queue-hint">Open pending queue</span>
        </a>

        <div class="card">
            <h3><?= e($activeDepartmentName) ?> Pending Bills</h3>
            <h2>₦<?= e($pendingBills) ?></h2>
        </div>
        <?php endif; ?>
    </section>

    <?php if ($canViewBillingRequestQueues): ?>
        <section class="card">
            <div class="section-header">
                <div>
                    <h2>Accounts Billing Queues</h2>
                    <p class="text-muted">Open pending billing requests directly by department or registration type.</p>
                </div>
                <a class="btn-secondary btn-sm" href="../modules/billing/billing_requests.php?status=Pending">All Pending Requests</a>
            </div>
            <div class="summary-grid dashboard-queue-grid">
                <?php foreach ($dashboardPaymentStatusQueues as $queue): ?>
                    <a class="summary-item dashboard-queue-button" href="<?= e((string)$queue['href']) ?>">
                        <span class="summary-label"><?= e((string)$queue['label']) ?></span>
                        <span class="summary-value"><?= (int)$queue['pending_count'] ?></span>
                        <span class="text-muted queue-hint"><?= e((string)$queue['description']) ?></span>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($dashboardPriorityBillingQueues as $queue): ?>
                    <a class="summary-item dashboard-queue-button" href="<?= e((string)$queue['href']) ?>">
                        <span class="summary-label"><?= e((string)$queue['label']) ?></span>
                        <span class="summary-value"><?= (int)$queue['pending_count'] ?></span>
                        <span class="text-muted queue-hint"><?= e((string)$queue['description']) ?></span>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($dashboardDepartmentBillingQueues as $queue): ?>
                    <a class="summary-item dashboard-queue-button" href="<?= e((string)$queue['href']) ?>">
                        <span class="summary-label"><?= e((string)$queue['label']) ?> Billing Requests</span>
                        <span class="summary-value"><?= (int)$queue['pending_count'] ?></span>
                        <span class="text-muted queue-hint">Open pending queue</span>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($dashboardRegistrationBillingQueues as $queue): ?>
                    <a class="summary-item dashboard-queue-button" href="<?= e((string)$queue['href']) ?>">
                        <span class="summary-label"><?= e((string)$queue['label']) ?></span>
                        <span class="summary-value"><?= (int)$queue['pending_count'] ?></span>
                        <span class="text-muted queue-hint"><?= e((string)$queue['description']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

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
