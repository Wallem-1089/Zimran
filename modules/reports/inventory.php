<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$permissionService->canViewInventoryReports($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to view inventory reports.');
}
$filters = reportsDateFilters();
$departments = $dashboardService->listReportDepartments();
$items = $dashboardService->listReportInventoryItems();
$report = $dashboardService->getInventoryReport($filters);
$dashboardService->recordReportView((int)($currentUser['id'] ?? 0), 'INVENTORY_REPORT_VIEWED');

$pageTitle = 'Inventory Summary';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div><h1>Inventory Summary</h1><p>Store stock balances and movement totals.</p></div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Inventory Summary</button>
            <a class="btn-secondary" href="index.php">Reports</a>
        </div>
    </div>
    <?php reportsFilterForm($filters, $departments, ['items' => $items]); ?>
    <div class="card">
        <h3>Transactions</h3>
        <?php if (empty($report['transactions'])): ?>
            <p class="text-muted">No stock movement found.</p>
        <?php else: ?>
            <table class="table"><thead><tr><th>Type</th><th>Transactions</th><th>Quantity</th></tr></thead><tbody>
            <?php foreach ($report['transactions'] as $row): ?>
                <tr><td><?= e((string)$row['transaction_type']) ?></td><td><?= (int)$row['total_transactions'] ?></td><td><?= e(number_format((float)$row['total_quantity'], 2)) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3>Current Balances</h3>
        <?php if (empty($report['balances'])): ?>
            <p class="text-muted">No stock balances found.</p>
        <?php else: ?>
            <table class="table"><thead><tr><th>Department</th><th>Item</th><th>Quantity</th></tr></thead><tbody>
            <?php foreach ($report['balances'] as $row): ?>
                <tr><td><?= e((string)$row['department_name']) ?></td><td><?= e((string)$row['item_code']) ?> - <?= e((string)$row['item_name']) ?></td><td><?= e(number_format((float)$row['quantity'], 2)) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
