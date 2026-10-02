<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$accountsTablesReady) {
    http_response_code(503);
    exit('Accounts tables are not available yet. Apply Migration 030 to enable this section.');
}

accountsRequireAccess($permissionService, $currentUser);

$filters = [
    'item_code' => trim((string)($_GET['item_code'] ?? '')),
    'item_name' => trim((string)($_GET['item_name'] ?? '')),
    'item_type' => trim((string)($_GET['item_type'] ?? '')),
    'department_id' => (int)($_GET['department_id'] ?? 0),
    'status' => trim((string)($_GET['status'] ?? 'all')),
];

$items = $accountsService->searchItems($filters, $currentUser);
$allItems = $accountsService->searchItems(['status' => 'all'], $currentUser);
$activeServices = count(array_filter($allItems, static fn (array $item): bool => !empty($item['is_active']) && (string)$item['item_type'] === 'Service'));
$activeDrugs = count(array_filter($allItems, static fn (array $item): bool => !empty($item['is_active']) && (string)$item['item_type'] === 'Drug'));
$activeConsumables = count(array_filter($allItems, static fn (array $item): bool => !empty($item['is_active']) && in_array((string)$item['item_type'], ['Consumable', 'Product'], true)));
$canViewBillingRequestQueues = $permissionService->canViewBillingRequests($currentUser);

function accountsClinicalBillingCount(PDO $pdo, string $sourceModule, array $statuses = ['Pending']): int
{
    if (!accountsTableExists($pdo, 'billing_requests')) {
        return 0;
    }

    $statuses = array_values(array_filter(
        array_map(static fn (mixed $status): string => trim((string)$status), $statuses),
        static fn (string $status): bool => $status !== ''
    ));
    if ($statuses === []) {
        return 0;
    }
    $statusPlaceholders = [];
    $params = [];
    foreach ($statuses as $index => $status) {
        $placeholder = ':status_' . $index;
        $statusPlaceholders[] = $placeholder;
        $params[$placeholder] = $status;
    }

    if (strcasecmp($sourceModule, 'Plaster') === 0) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM billing_requests WHERE status IN (' . implode(', ', $statusPlaceholders) . ") AND source_module IN ('Plaster', 'POP')");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    $params[':source_module'] = $sourceModule;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM billing_requests WHERE status IN (' . implode(', ', $statusPlaceholders) . ') AND source_module = :source_module');
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function accountsPendingClinicalBillingCount(PDO $pdo, string $sourceModule): int
{
    return accountsClinicalBillingCount($pdo, $sourceModule, ['Pending']);
}

function accountsPendingRegistrationBillingCount(PDO $pdo, string $billingType, ?string $registrationType = null): int
{
    if (!accountsTableExists($pdo, 'patient_registration_billing_requests')) {
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

$departmentBillingQueues = [
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

foreach ($departmentBillingQueues as $index => $queue) {
    $pendingCount = accountsClinicalBillingCount($pdo, (string)$queue['source_module'], ['Pending']);
    $chargedCount = accountsClinicalBillingCount($pdo, (string)$queue['source_module'], ['Charged']);
    $departmentBillingQueues[$index]['pending_count'] = $pendingCount;
    $departmentBillingQueues[$index]['charged_count'] = $chargedCount;
    $departmentBillingQueues[$index]['actionable_count'] = $pendingCount + $chargedCount;
    $departmentBillingQueues[$index]['href'] = '../billing/billing_requests.php?' . http_build_query([
        'status' => 'Actionable',
        'source_module' => $queue['source_module'],
    ]);
}

$priorityBillingQueues = [
    [
        'label' => 'Patient Registration',
        'description' => 'Normal registration payments',
        'pending_count' => accountsPendingRegistrationBillingCount($pdo, 'InitialRegistration', 'Normal'),
        'href' => '../billing/registration_requests.php?' . http_build_query([
            'status' => 'Pending',
            'billing_type' => 'InitialRegistration',
            'registration_type' => 'Normal',
        ]),
    ],
    [
        'label' => 'Consultation Fee',
        'description' => 'Consultation billing requests',
        'pending_count' => accountsClinicalBillingCount($pdo, 'Consultation', ['Pending', 'Charged']),
        'href' => '../billing/billing_requests.php?' . http_build_query([
            'status' => 'Actionable',
            'source_module' => 'Consultation',
        ]),
    ],
    [
        'label' => 'Patient Renewal',
        'description' => 'Monthly renewal payments',
        'pending_count' => accountsPendingRegistrationBillingCount($pdo, 'MonthlyRenewal'),
        'href' => '../billing/registration_requests.php?' . http_build_query([
            'status' => 'Pending',
            'billing_type' => 'MonthlyRenewal',
        ]),
    ],
    [
        'label' => 'Emergency Registration',
        'description' => 'Emergency registration payments',
        'pending_count' => accountsPendingRegistrationBillingCount($pdo, 'InitialRegistration', 'Emergency'),
        'href' => '../billing/registration_requests.php?' . http_build_query([
            'status' => 'Pending',
            'billing_type' => 'InitialRegistration',
            'registration_type' => 'Emergency',
        ]),
    ],
];

$registrationBillingQueues = [];
$pageTitle = 'Price Catalogue';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <style>
        .accounts-queue-grid .accounts-queue-button {
            color: inherit;
            display: block;
            min-height: 112px;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .accounts-queue-grid .accounts-queue-button:hover,
        .accounts-queue-grid .accounts-queue-button:focus {
            border-color: #2563eb;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.12);
            transform: translateY(-1px);
        }

        .accounts-queue-grid .queue-hint {
            display: block;
            margin-top: 0.35rem;
            font-size: 0.82rem;
        }
    </style>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['validation_errors'])): ?>
        <div class="alert-danger"><?= e(implode(' ', (array)$_SESSION['validation_errors'])) ?></div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>
    <div class="page-header">
        <div>
            <h1>Price Catalogue</h1>
            <p>Hospital-wide billable items and Pharmacy-managed price master data.</p>
        </div>
        <div>
            <?php if ($permissionService->canCreateBillableItems($currentUser)): ?>
                <a class="btn-primary" href="create.php">Create Item</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canViewBillingRequestQueues): ?>
        <div class="card">
            <div class="section-header">
                <div>
                    <h2>Accounts Billing Queues</h2>
                    <p class="text-muted">Open billing requests directly by department, including auto-charged requests awaiting payment.</p>
                </div>
                <div class="form-actions">
                    <a class="btn-secondary btn-sm" href="../billing/billing_requests.php?status=Charged">Auto-charged Bills</a>
                    <a class="btn-secondary btn-sm" href="../billing/billing_requests.php?status=Actionable">All Actionable Requests</a>
                </div>
            </div>
            <div class="summary-grid accounts-queue-grid">
                <?php foreach ($priorityBillingQueues as $queue): ?>
                    <a class="summary-item accounts-queue-button" href="<?= e((string)$queue['href']) ?>">
                        <span class="summary-label"><?= e((string)$queue['label']) ?></span>
                        <span class="summary-value"><?= (int)$queue['pending_count'] ?></span>
                        <span class="text-muted queue-hint"><?= e((string)$queue['description']) ?></span>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($departmentBillingQueues as $queue): ?>
                    <a class="summary-item accounts-queue-button" href="<?= e((string)$queue['href']) ?>">
                        <span class="summary-label"><?= e((string)$queue['label']) ?> Billing Requests</span>
                        <span class="summary-value"><?= (int)$queue['actionable_count'] ?></span>
                        <span class="text-muted queue-hint"><?= (int)$queue['pending_count'] ?> pending · <?= (int)$queue['charged_count'] ?> auto-charged</span>
                    </a>
                <?php endforeach; ?>
                <?php foreach ($registrationBillingQueues as $queue): ?>
                    <a class="summary-item accounts-queue-button" href="<?= e((string)$queue['href']) ?>">
                        <span class="summary-label"><?= e((string)$queue['label']) ?></span>
                        <span class="summary-value"><?= (int)$queue['pending_count'] ?></span>
                        <span class="text-muted queue-hint"><?= e((string)$queue['description']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="summary-grid">
        <div class="summary-item"><span class="summary-label">Catalogue Items</span> <span class="summary-value"><?= count($allItems) ?></span></div>
        <div class="summary-item"><span class="summary-label">Active Services</span> <span class="summary-value"><?= $activeServices ?></span></div>
        <div class="summary-item"><span class="summary-label">Active Drugs</span> <span class="summary-value"><?= $activeDrugs ?></span></div>
        <div class="summary-item"><span class="summary-label">Active Consumables</span> <span class="summary-value"><?= $activeConsumables ?></span></div>
    </div>

    <form method="get" class="card">
        <div class="form-grid">
            <div class="form-group">
                <label for="item_code">Code</label>
                <input id="item_code" name="item_code" value="<?= e($filters['item_code']) ?>">
            </div>
            <div class="form-group">
                <label for="item_name">Name</label>
                <input id="item_name" name="item_name" value="<?= e($filters['item_name']) ?>">
            </div>
            <div class="form-group">
                <label for="item_type">Type</label>
                <select id="item_type" name="item_type">
                    <option value="">All</option>
                    <?php foreach (['Drug', 'Consumable', 'Service'] as $type): ?>
                        <option value="<?= e($type) ?>" <?= $filters['item_type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id">
                    <option value="0">All</option>
                    <?php foreach ($accountsDepartmentOptions as $department): ?>
                        <option value="<?= (int)$department['id'] ?>" <?= $filters['department_id'] === (int)$department['id'] ? 'selected' : '' ?>>
                            <?= e((string)$department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Filter</button>
            <a class="btn-secondary" href="index.php">Reset</a>
        </div>
    </form>

    <div class="card">
        <?php if ($items === []): ?>
            <p class="text-muted">No billable items found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Department</th>
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= e((string)$item['item_code']) ?></td>
                                <td><?= e((string)$item['item_name']) ?></td>
                                <td><?= e((string)$item['item_type']) ?></td>
                                <td><?= e((string)($item['department_name'] ?? '-')) ?></td>
                                <td><?= e((string)($item['unit'] ?? '-')) ?></td>
                                <td><?= e(number_format((float)$item['unit_price'], 2)) ?></td>
                                <td><?= !empty($item['is_active']) ? 'Active' : 'Inactive' ?></td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$item['id'] ?>">View</a>
                                    <?php if ($permissionService->canEditBillableItems($currentUser)): ?>
                                        <a class="btn-secondary btn-sm" href="edit.php?id=<?= (int)$item['id'] ?>">Edit</a>
                                    <?php endif; ?>
                                    <?php if ($permissionService->canManageBillableItemStatus($currentUser)): ?>
                                        <form method="post" action="action.php" style="display:inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                            <input type="hidden" name="action" value="<?= !empty($item['is_active']) ? 'deactivate' : 'activate' ?>">
                                            <button class="btn-secondary btn-sm" type="submit"><?= !empty($item['is_active']) ? 'Deactivate' : 'Activate' ?></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
