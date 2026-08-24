<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady) {
    http_response_code(503);
    exit('Store tables are not available yet. Apply Migration 031 to enable this section.');
}

storeRequireAccess($permissionService, $currentUser);

$filters = [
    'item_code' => trim((string)($_GET['item_code'] ?? '')),
    'item_name' => trim((string)($_GET['item_name'] ?? '')),
    'category' => trim((string)($_GET['category'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? 'all')),
    'billable_item_id' => (int)($_GET['billable_item_id'] ?? 0),
];

$items = $storeService->searchItems($filters, $currentUser);
$allItems = $storeService->searchItems(['status' => 'all'], $currentUser);
$activeItems = count(array_filter($allItems, static fn (array $item): bool => !empty($item['is_active'])));

$pageTitle = 'Store Inventory';
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
            <h1>Inventory Items</h1>
            <p>Store-owned inventory and stock movements.</p>
        </div>
        <div class="form-actions">
            <?php if ($permissionService->canManageInventoryItems($currentUser)): ?>
                <a class="btn-primary" href="create.php">Create Item</a>
            <?php endif; ?>
            <a class="btn-secondary" href="ledger.php">Stock Ledger</a>
            <a class="btn-secondary" href="department_stock.php">Stock by Department</a>
            <?php if (($storeExternalSalesReady ?? false) && $permissionService->canViewExternalSales($currentUser)): ?>
                <a class="btn-secondary" href="external_sales.php">External Sales</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item"><span class="summary-label">Inventory Items</span> <span class="summary-value"><?= count($allItems) ?></span></div>
        <div class="summary-item"><span class="summary-label">Active Items</span> <span class="summary-value"><?= $activeItems ?></span></div>
        <div class="summary-item"><span class="summary-label">Inactive Items</span> <span class="summary-value"><?= count($allItems) - $activeItems ?></span></div>
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
                <label for="category">Category</label>
                <input id="category" name="category" value="<?= e($filters['category']) ?>">
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
            <p class="text-muted">No inventory items found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Billable Link</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= e((string)$item['item_code']) ?></td>
                                <td><?= e((string)$item['item_name']) ?></td>
                                <td><?= e((string)$item['category']) ?></td>
                                <td><?= e((string)$item['unit']) ?></td>
                                <td>
                                    <?php if (!empty($item['billable_item_id'])): ?>
                                        <?= e((string)($item['billable_item_code'] ?? '-')) ?> — <?= e((string)($item['billable_item_name'] ?? '-')) ?>
                                        <?php if (!empty($item['billable_item_price_display'])): ?>
                                            (<?= e((string)$item['billable_item_price_display']) ?>)
                                        <?php endif; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($item['is_active']) ? 'Active' : 'Inactive' ?></td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$item['id'] ?>">View</a>
                                    <?php if ($permissionService->canManageInventoryItems($currentUser)): ?>
                                        <a class="btn-secondary btn-sm" href="edit.php?id=<?= (int)$item['id'] ?>">Edit</a>
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
