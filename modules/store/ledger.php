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
$rows = $itemId > 0 ? $storeService->getItemLedger($itemId, $currentUser) : [];

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
    <div class="card">
        <?php if ($itemId <= 0): ?>
            <p class="text-muted">Choose an inventory item from the item view to see its ledger.</p>
        <?php elseif ($rows === []): ?>
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
                            <th>Remarks</th>
                            <th>Performed By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
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
