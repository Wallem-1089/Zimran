<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady || !$storeExternalSalesReady) {
    http_response_code(503);
    exit('External sales tables are not available yet. Apply Migration 043 to enable this section.');
}

storeRequireCreateExternalSaleAccess($permissionService, $currentUser);

$storeDepartmentId = storeStoreDepartmentId($pdo);
$storeStock = $storeDepartmentId !== null
    ? $storeService->listDepartmentStock($storeDepartmentId, $currentUser)
    : [];
$saleableStock = array_values(array_filter($storeStock, static function (array $row): bool {
    return !empty($row['is_active'])
        && !empty($row['billable_item_code'])
        && isset($row['billable_item_price'])
        && (float)($row['quantity'] ?? 0) > 0;
}));

$old = (array)($_SESSION['old_input'] ?? []);

$pageTitle = 'New External Store Sale';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?>
        <div class="alert-danger"><?= e(implode(' ', (array)$_SESSION['validation_errors'])) ?></div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1>New External Sale</h1>
            <p>Sell Store stock to a non-patient customer using Accounts catalogue price.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="external_sales.php">Back to Sales</a>
        </div>
    </div>

    <section class="card">
        <?php if ($storeDepartmentId === null): ?>
            <p class="alert-danger">Store department is not configured.</p>
        <?php elseif ($saleableStock === []): ?>
            <p class="text-muted">No saleable Store stock found. External sales require Store stock with an active Accounts price link.</p>
        <?php else: ?>
            <form method="post" action="external_sale_save.php">
                <?= csrfField() ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="inventory_item_id">Item <span class="required">*</span></label>
                        <select id="inventory_item_id" name="inventory_item_id" required>
                            <option value="">Select item</option>
                            <?php foreach ($saleableStock as $row): ?>
                                <?php
                                    $label = sprintf(
                                        '%s — %s / %s available: %s',
                                        (string)$row['item_name'],
                                        number_format((float)$row['billable_item_price'], 2),
                                        (string)$row['unit'],
                                        number_format((float)$row['quantity'], 2)
                                    );
                                ?>
                                <option value="<?= (int)$row['inventory_item_id'] ?>" <?= (int)($old['inventory_item_id'] ?? 0) === (int)$row['inventory_item_id'] ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Quantity <span class="required">*</span></label>
                        <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" required value="<?= e((string)($old['quantity'] ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Payment Method <span class="required">*</span></label>
                        <select id="payment_method" name="payment_method" required>
                            <?php foreach (['Cash', 'Card', 'Transfer', 'Other'] as $method): ?>
                                <option value="<?= e($method) ?>" <?= (string)($old['payment_method'] ?? 'Cash') === $method ? 'selected' : '' ?>><?= e($method) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reference">Payment Reference</label>
                        <input id="reference" name="reference" maxlength="255" value="<?= e((string)($old['reference'] ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="customer_name">Customer Name</label>
                        <input id="customer_name" name="customer_name" maxlength="150" value="<?= e((string)($old['customer_name'] ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="customer_phone">Customer Phone</label>
                        <input id="customer_phone" name="customer_phone" maxlength="50" value="<?= e((string)($old['customer_phone'] ?? '')) ?>">
                    </div>
                </div>

                <?php unset($_SESSION['old_input']); ?>

                <div class="form-actions">
                    <button class="btn-primary" type="submit">Complete Sale</button>
                    <a class="btn-secondary" href="external_sales.php">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
