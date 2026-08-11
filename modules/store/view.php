<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady) {
    http_response_code(503);
    exit('Store tables are not available yet. Apply Migration 031 to enable this section.');
}

storeRequireAccess($permissionService, $currentUser);

$itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$item = storeRequireItem($storeService, $itemId, $currentUser);
$ledgerPreview = array_slice($storeService->getItemLedger($itemId, $currentUser), 0, 10);
$departmentStock = $storeService->listDepartmentStock(null, $currentUser);
$currentStocks = array_values(array_filter($departmentStock, static fn (array $row): bool => (int)$row['inventory_item_id'] === $itemId));

$pageTitle = 'View Inventory Item';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
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
            <h1><?= e((string)$item['item_name']) ?></h1>
            <p><?= e((string)$item['item_code']) ?> · <?= e((string)$item['category']) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">Back</a>
            <a class="btn-secondary" href="ledger.php?id=<?= $itemId ?>">Ledger</a>
            <a class="btn-secondary" href="department_stock.php?item_id=<?= $itemId ?>">Department Stock</a>
            <?php if ($permissionService->canManageInventoryItems($currentUser)): ?>
                <a class="btn-primary" href="edit.php?id=<?= $itemId ?>">Edit</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Unit</span><span class="summary-value"><?= e((string)$item['unit']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span><span class="summary-value"><?= !empty($item['is_active']) ? 'Active' : 'Inactive' ?></span></div>
            <div class="summary-item"><span class="summary-label">Billable Link</span><span class="summary-value">
                <?php if (!empty($item['billable_item_id'])): ?>
                    <?= e((string)($item['billable_item_code'] ?? '-')) ?> — <?= e((string)($item['billable_item_name'] ?? '-')) ?>
                    <?php if (!empty($item['billable_item_price_display'])): ?>
                        (<?= e((string)$item['billable_item_price_display']) ?>)
                    <?php endif; ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </span></div>
            <div class="summary-item"><span class="summary-label">Created By</span><span class="summary-value"><?= e((string)($item['created_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Created At</span><span class="summary-value"><?= e((string)($item['created_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Updated By</span><span class="summary-value"><?= e((string)($item['updated_by_name'] ?? '-')) ?></span></div>
        </div>
        <?php if (!empty($item['description'])): ?>
            <p><?= nl2br(e((string)$item['description'])) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="page-header">
            <div>
                <h2>Stock Actions</h2>
                <p>Receive, issue, return, and adjust stock through immutable movements.</p>
            </div>
        </div>
        <div class="form-actions">
            <?php if ($permissionService->canReceiveStock($currentUser)): ?><a class="btn-secondary" href="receive.php?id=<?= $itemId ?>">Receive Stock</a><?php endif; ?>
            <?php if ($permissionService->canIssueStock($currentUser)): ?><a class="btn-secondary" href="issue.php?id=<?= $itemId ?>">Issue Stock</a><?php endif; ?>
            <?php if ($permissionService->canReturnStock($currentUser)): ?><a class="btn-secondary" href="return.php?id=<?= $itemId ?>">Return Stock</a><?php endif; ?>
            <?php if ($permissionService->canAdjustStock($currentUser)): ?><a class="btn-secondary" href="adjust.php?id=<?= $itemId ?>">Adjust Stock</a><?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>Current Department Stock</h2>
        <?php if ($currentStocks === []): ?>
            <p class="text-muted">No department stock recorded yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Department</th><th>Quantity</th><th>Updated</th></tr></thead>
                    <tbody>
                        <?php foreach ($currentStocks as $stock): ?>
                            <tr>
                                <td><?= e((string)$stock['department_name']) ?></td>
                                <td><?= e((string)$stock['quantity_display']) ?></td>
                                <td><?= e((string)($stock['updated_at'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Recent Ledger</h2>
        <?php if ($ledgerPreview === []): ?>
            <p class="text-muted">No stock movements recorded.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Reference</th>
                            <th>Performer</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ledgerPreview as $entry): ?>
                            <tr>
                                <td><?= e((string)$entry['transaction_type']) ?></td>
                                <td><?= e(number_format((float)$entry['quantity'], 2)) ?></td>
                                <td><?= e((string)($entry['from_department_name'] ?? '-')) ?></td>
                                <td><?= e((string)($entry['to_department_name'] ?? '-')) ?></td>
                                <td><?= e((string)($entry['reference'] ?? '-')) ?></td>
                                <td><?= e((string)($entry['performed_by_name'] ?? '-')) ?></td>
                                <td><?= e((string)($entry['created_at'] ?? '-')) ?></td>
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

