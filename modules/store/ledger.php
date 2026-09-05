<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady) {
    http_response_code(503);
    exit('Store tables are not available yet. Apply Migration 031 to enable this section.');
}

storeRequireAccess($permissionService, $currentUser);

$itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$item = $itemId > 0 ? storeRequireItem($storeService, $itemId, $currentUser) : null;
$filters = [
    'item_id' => $itemId > 0 ? $itemId : (filter_input(INPUT_GET, 'item_id', FILTER_VALIDATE_INT) ?: null),
    'department_id' => filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT) ?: null,
    'transaction_type' => $_GET['transaction_type'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
];
$rows = $storeService->listStockLedger($filters, $currentUser, 200);
$items = $storeService->searchItems(['status' => 'all'], $currentUser);

$pageTitle = 'Stock Ledger';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Stock Ledger</h1>
            <p><?php if ($item): ?><?= e((string)$item['item_code']) ?> — <?= e((string)$item['item_name']) ?><?php else: ?>All item movements<?php endif; ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Bin Card / Ledger</button>
            <a class="btn-secondary" href="index.php">Inventory Items</a>
            <?php if ($item): ?><a class="btn-secondary" href="view.php?id=<?= $itemId ?>">Item</a><?php endif; ?>
        </div>
    </div>

    <form method="get" action="ledger.php" class="card no-print">
        <?php if ($item): ?>
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
        <?php endif; ?>
        <div class="form-grid">
            <?php if (!$item): ?>
                <div class="form-group">
                    <label for="item_id">Inventory Item</label>
                    <select id="item_id" name="item_id">
                        <option value="">All items</option>
                        <?php foreach ($items as $option): ?>
                            <option value="<?= (int)$option['id'] ?>" <?= (int)($filters['item_id'] ?? 0) === (int)$option['id'] ? 'selected' : '' ?>>
                                <?= e((string)$option['item_code']) ?> — <?= e((string)$option['item_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id">
                    <option value="">All departments</option>
                    <?php foreach ($storeDepartmentOptions as $department): ?>
                        <option value="<?= (int)$department['id'] ?>" <?= (int)($filters['department_id'] ?? 0) === (int)$department['id'] ? 'selected' : '' ?>>
                            <?= e((string)$department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="transaction_type">Movement Type</label>
                <select id="transaction_type" name="transaction_type">
                    <option value="">All movements</option>
                    <?php foreach (['Receipt', 'Issue', 'Return', 'Adjustment', 'Consumption'] as $type): ?>
                        <option value="<?= e($type) ?>" <?= (string)($filters['transaction_type'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="date_from">From</label>
                <input id="date_from" name="date_from" type="date" value="<?= e((string)($filters['date_from'] ?? '')) ?>">
            </div>

            <div class="form-group">
                <label for="date_to">To</label>
                <input id="date_to" name="date_to" type="date" value="<?= e((string)($filters['date_to'] ?? '')) ?>">
            </div>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Filter Ledger</button>
            <a class="btn-secondary" href="ledger.php">Clear</a>
        </div>
    </form>

    <div class="card">
        <div class="section-header">
            <div>
                <h2>Stock Movements</h2>
                <p class="text-muted">Showing latest <?= e((string)count($rows)) ?> matching movement<?= count($rows) === 1 ? '' : 's' ?>.</p>
            </div>
        </div>

        <?php if ($rows === []): ?>
            <p class="text-muted">No stock movements recorded.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Reference</th>
                            <th>Remarks</th>
                            <th>Performed By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= e((string)($row['item_name'] ?? '-')) ?></strong>
                                    <br><small class="text-muted"><?= e((string)($row['item_code'] ?? '-')) ?></small>
                                </td>
                                <td><?= e((string)$row['transaction_type']) ?></td>
                                <td><?= e(number_format((float)$row['quantity'], 2)) ?></td>
                                <td><?= e((string)($row['from_department_name'] ?? '-')) ?></td>
                                <td><?= e((string)($row['to_department_name'] ?? '-')) ?></td>
                                <td><?= e((string)($row['reference'] ?? '-')) ?></td>
                                <td><?= e((string)($row['remarks'] ?? '-')) ?></td>
                                <td><?= e((string)($row['performed_by_name'] ?? '-')) ?></td>
                                <td><?= e((string)($row['created_at'] ?? '-')) ?></td>
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
