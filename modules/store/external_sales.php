<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady || !$storeExternalSalesReady) {
    http_response_code(503);
    exit('External sales tables are not available yet. Apply Migration 043 to enable this section.');
}

storeRequireExternalSalesAccess($permissionService, $currentUser);

$filters = [
    'search' => trim((string)($_GET['search'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
];

$sales = $storeService->listExternalSales($filters, $currentUser);

$pageTitle = 'External Store Sales';
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
            <h1>External Store Sales</h1>
            <p>Non-patient walk-in sales. These do not create encounters, patient charges, or invoices.</p>
        </div>
        <div class="form-actions">
            <?php if ($permissionService->canCreateExternalSale($currentUser)): ?>
                <a class="btn-primary" href="external_sale_create.php">New External Sale</a>
            <?php endif; ?>
            <a class="btn-secondary" href="index.php">Inventory Items</a>
        </div>
    </div>

    <form method="get" class="card">
        <div class="form-grid">
            <div class="form-group">
                <label for="search">Search</label>
                <input id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Sale number, customer, phone">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (['' => 'All', 'Completed' => 'Completed', 'Cancelled' => 'Cancelled'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Filter</button>
            <a class="btn-secondary" href="external_sales.php">Reset</a>
        </div>
    </form>

    <section class="card">
        <?php if ($sales === []): ?>
            <p class="text-muted">No external sales found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sale No.</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Sold By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><?= e((string)$sale['sale_number']) ?></td>
                                <td><?= e((string)($sale['customer_name'] ?? 'Walk-in Customer')) ?></td>
                                <td><?= e((string)$sale['total_amount_display']) ?></td>
                                <td><?= e((string)$sale['payment_method']) ?></td>
                                <td><?= e((string)($sale['sold_by_name'] ?? '—')) ?></td>
                                <td><?= e(date('d M Y h:i A', strtotime((string)$sale['created_at']))) ?></td>
                                <td><?= e((string)$sale['status']) ?></td>
                                <td class="table-actions">
                                    <a class="btn-secondary btn-sm" href="external_sale_view.php?id=<?= (int)$sale['id'] ?>">View</a>
                                    <?php if ((string)$sale['status'] === 'Completed' && $permissionService->canCancelExternalSale($currentUser)): ?>
                                        <a class="btn-danger btn-sm" href="external_sale_view.php?id=<?= (int)$sale['id'] ?>#cancel-sale">Cancel</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
