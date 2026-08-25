<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady || !$patientStockUsageTablesReady) {
    http_response_code(503);
    exit('Patient Stock Usage tables are not available yet. Apply Migration 053 to enable this section.');
}

storeRequireAccess($permissionService, $currentUser);

if (!$permissionService->canViewPatientStockUsage($currentUser)) {
    http_response_code(403);
    exit('Patient Stock Usage access denied.');
}

$departmentId = filter_input(INPUT_GET, 'department', FILTER_VALIDATE_INT) ?: 0;
$departments = $storeDepartmentOptions;

if ($departmentId > 0) {
    $usageRecords = $patientStockUsageService->listByDepartment($departmentId, $currentUser);
} else {
    $usageRecords = [];
    foreach ($departments as $department) {
        $usageRecords = array_merge(
            $usageRecords,
            $patientStockUsageService->listByDepartment((int)$department['id'], $currentUser)
        );
    }
    usort(
        $usageRecords,
        static fn (array $a, array $b): int => strcmp((string)$b['created_at'], (string)$a['created_at'])
    );
}

$pageTitle = 'Patient Stock Usage';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Patient Stock Usage</h1>
            <p>Read-only view of department stock consumed for patient encounters.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">Inventory Items</a>
            <a class="btn-secondary" href="department_stock.php">Stock by Department</a>
            <a class="btn-secondary" href="ledger.php">Stock Ledger</a>
        </div>
    </div>

    <form method="get" class="card">
        <div class="form-grid">
            <div class="form-group">
                <label for="department">Department</label>
                <select id="department" name="department">
                    <option value="0">All departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= (int)$department['id'] ?>" <?= $departmentId === (int)$department['id'] ? 'selected' : '' ?>>
                            <?= e((string)$department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Filter</button>
            <a class="btn-secondary" href="patient_stock_usage.php">Reset</a>
        </div>
    </form>

    <div class="card">
        <?php if ($usageRecords === []): ?>
            <p class="text-muted">No patient stock usage has been recorded.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Hospital No.</th>
                            <th>Visit</th>
                            <th>Department</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Billing Request</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usageRecords as $usage): ?>
                            <tr>
                                <td><?= e((string)$usage['created_at']) ?></td>
                                <td><?= e((string)($usage['patient_name'] ?? '-')) ?></td>
                                <td><?= e((string)($usage['hospital_number'] ?? '-')) ?></td>
                                <td><?= e((string)($usage['visit_number'] ?? ('#' . (int)$usage['visit_id']))) ?></td>
                                <td><?= e((string)($usage['department_name'] ?? '-')) ?></td>
                                <td><?= e((string)($usage['item_name'] ?? '-')) ?></td>
                                <td><?= e((string)$usage['quantity']) ?> <?= e((string)($usage['unit'] ?? '')) ?></td>
                                <td>
                                    <?= !empty($usage['billing_request_id'])
                                        ? '#' . (int)$usage['billing_request_id'] . ' ' . e((string)($usage['billing_request_status'] ?? ''))
                                        : 'Not requested' ?>
                                </td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="../patient_stock_usage/view.php?id=<?= (int)$usage['id'] ?>">View</a>
                                    <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$usage['visit_id'] ?>&tab=stock_usage">Open Encounter</a>
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
