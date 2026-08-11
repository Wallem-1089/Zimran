<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady) {
    http_response_code(503);
    exit('Store tables are not available yet. Apply Migration 031 to enable this section.');
}

storeRequireAccess($permissionService, $currentUser);

$departmentId = filter_input(INPUT_GET, 'department_id', FILTER_VALIDATE_INT) ?: null;
$rows = $storeService->listDepartmentStock($departmentId, $currentUser);

$pageTitle = 'Stock by Department';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Stock by Department</h1>
            <p>Current balance cache maintained from Store transactions.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">Inventory Items</a>
        </div>
    </div>
    <form method="get" class="card">
        <div class="form-group">
            <label for="department_id">Department</label>
            <select id="department_id" name="department_id">
                <option value="">All Departments</option>
                <?php foreach ($storeDepartmentOptions as $department): ?>
                    <option value="<?= (int)$department['id'] ?>" <?= $departmentId === (int)$department['id'] ? 'selected' : '' ?>>
                        <?= e((string)$department['department_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Filter</button>
            <a class="btn-secondary" href="department_stock.php">Reset</a>
        </div>
    </form>
    <div class="card">
        <?php if ($rows === []): ?>
            <p class="text-muted">No stock balances available.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Quantity</th>
                            <th>Billable Link</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e((string)$row['department_name']) ?></td>
                                <td><?= e((string)$row['item_name']) ?></td>
                                <td><?= e((string)$row['category']) ?></td>
                                <td><?= e((string)$row['unit']) ?></td>
                                <td><?= e((string)$row['quantity_display']) ?></td>
                                <td>
                                    <?php if (!empty($row['billable_item_id'])): ?>
                                        <?= e((string)($row['billable_item_code'] ?? '-')) ?> — <?= e((string)($row['billable_item_name'] ?? '-')) ?>
                                        <?php if (!empty($row['billable_item_price_display'])): ?>
                                            (<?= e((string)$row['billable_item_price_display']) ?>)
                                        <?php endif; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?= e((string)($row['updated_at'] ?? '-')) ?></td>
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

