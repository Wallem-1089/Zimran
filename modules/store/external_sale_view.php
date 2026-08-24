<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady || !$storeExternalSalesReady) {
    http_response_code(503);
    exit('External sales tables are not available yet. Apply Migration 043 to enable this section.');
}

storeRequireExternalSaleReceiptAccess($permissionService, $currentUser);

$saleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$sale = $storeService->getExternalSaleById($saleId, $currentUser);

if (!$sale) {
    http_response_code(404);
    exit('External sale not found.');
}

$pageTitle = 'External Sale Receipt';
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
            <h1>External Sale Receipt</h1>
            <p><?= e((string)$sale['sale_number']) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print</button>
            <a class="btn-secondary" href="external_sales.php">Back to Sales</a>
        </div>
    </div>

    <section class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Customer</span> <span class="summary-value"><?= e((string)($sale['customer_name'] ?? 'Walk-in Customer')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Phone</span> <span class="summary-value"><?= e((string)($sale['customer_phone'] ?? '—')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Payment</span> <span class="summary-value"><?= e((string)$sale['payment_method']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Reference</span> <span class="summary-value"><?= e((string)($sale['reference'] ?? '—')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Sold By</span> <span class="summary-value"><?= e((string)($sale['sold_by_name'] ?? '—')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Date</span> <span class="summary-value"><?= e(date('d M Y h:i A', strtotime((string)$sale['created_at']))) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$sale['status']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Total</span> <span class="summary-value"><?= e((string)$sale['total_amount_display']) ?></span></div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ((array)$sale['items'] as $item): ?>
                        <tr>
                            <td><?= e((string)$item['item_name']) ?></td>
                            <td><?= e(number_format((float)$item['quantity'], 2)) ?> <?= e((string)($item['unit'] ?? '')) ?></td>
                            <td><?= e(number_format((float)$item['unit_price'], 2)) ?></td>
                            <td><?= e(number_format((float)$item['amount'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ((string)$sale['status'] === 'Completed' && $permissionService->canCancelExternalSale($currentUser)): ?>
        <section class="card" id="cancel-sale">
            <h2>Cancel Sale</h2>
            <p class="text-muted">Cancellation keeps the sale record. It does not automatically reverse stock; record a Store stock adjustment/return if correction is required.</p>
            <form method="post" action="external_sale_cancel.php" onsubmit="return confirm('Cancel this external sale?');">
                <?= csrfField() ?>
                <input type="hidden" name="external_sale_id" value="<?= (int)$sale['id'] ?>">
                <div class="form-group">
                    <label for="cancel_reason">Reason <span class="required">*</span></label>
                    <textarea id="cancel_reason" name="cancel_reason" rows="3" required></textarea>
                </div>
                <button class="btn-danger" type="submit">Cancel Sale</button>
            </form>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
