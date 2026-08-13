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
$activeProducts = count(array_filter($allItems, static fn (array $item): bool => !empty($item['is_active']) && (string)$item['item_type'] === 'Product'));

$pageTitle = 'Price Catalogue';
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
            <h1>Price Catalogue</h1>
            <p>Hospital-wide billable items and price master data.</p>
        </div>
        <div>
            <?php if ($permissionService->canCreateBillableItems($currentUser)): ?>
                <a class="btn-primary" href="create.php">Create Item</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item"><span class="summary-label">Catalogue Items</span> <span class="summary-value"><?= count($allItems) ?></span></div>
        <div class="summary-item"><span class="summary-label">Active Services</span> <span class="summary-value"><?= $activeServices ?></span></div>
        <div class="summary-item"><span class="summary-label">Active Products</span> <span class="summary-value"><?= $activeProducts ?></span></div>
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
                    <?php foreach (['Service', 'Product'] as $type): ?>
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
