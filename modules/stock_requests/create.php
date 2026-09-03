<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
stockRequestRequireReady($stockRequestTablesReady);

if (!$permissionService->canCreateStockRequest($currentUser)) {
    http_response_code(403);
    exit('Stock request creation denied.');
}

$items = stockRequestInventoryItems($pdo);
$departments = stockRequestDepartments($pdo);
$canChooseDepartment = $permissionService->canReviewStockRequest($currentUser)
    || $permissionService->isAdministrator($currentUser);
$old = $_SESSION['old_stock_request'] ?? [];
unset($_SESSION['old_stock_request']);
$enableWritingMode = $permissionService->canUseConsultationHandwriting($currentUser);

$pageTitle = 'New Stock Request';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?><div class="alert-danger"><ul><?php foreach ((array)$_SESSION['validation_errors'] as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?></ul></div><?php unset($_SESSION['validation_errors']); endif; ?>

    <div class="page-header">
        <div><h1>New Stock Request</h1><p>Request items from Store. This does not move stock until Store issues it.</p></div>
        <div><a class="btn-secondary" href="index.php">Back</a></div>
    </div>

    <form class="card" method="post" action="save.php" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
        <?= csrfField() ?>
        <?php if ($canChooseDepartment): ?>
            <label>Requesting Department
                <select name="requesting_department_id" required>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= (int)$department['id'] ?>" <?= (int)($old['requesting_department_id'] ?? 0) === (int)$department['id'] ? 'selected' : '' ?>>
                            <?= e((string)$department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Stock Request Entry Mode'); ?>
        <?php hmsRenderHandwritingTextarea('reason', 'Reason / Notes', (string)($old['reason'] ?? ''), 4, false, $enableWritingMode, 2000); ?>

        <h3>Requested Items</h3>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Item</th><th>Quantity</th><th>Notes</th></tr></thead>
                <tbody>
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <tr>
                        <td>
                            <select name="inventory_item_id[]">
                                <option value="">Select item</option>
                                <?php foreach ($items as $item): ?>
                                    <option value="<?= (int)$item['id'] ?>" <?= (int)($old['inventory_item_id'][$i] ?? 0) === (int)$item['id'] ? 'selected' : '' ?>>
                                        <?= e((string)$item['item_code']) ?> - <?= e((string)$item['item_name']) ?> <?= e((string)($item['unit'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input name="quantity_requested[]" type="number" step="0.01" min="0" value="<?= e((string)($old['quantity_requested'][$i] ?? '')) ?>"></td>
                        <td><input name="notes[]" maxlength="1000" value="<?= e((string)($old['notes'][$i] ?? '')) ?>"></td>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Submit Request</button>
            <a class="btn-secondary" href="index.php">Cancel</a>
        </div>
    </form>
    <?php hmsRenderHandwritingScript($enableWritingMode); ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
