<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if (!$accountsTablesReady) {
    http_response_code(503);
    exit('Accounts tables are not available yet. Apply Migration 030 to enable this section.');
}

$item = $accountsService->getItemById($itemId, $currentUser);
if (!$item) {
    http_response_code(404);
    exit('Billable item not found.');
}

$pageTitle = 'Billable Item';
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
            <p><?= e((string)$item['item_code']) ?> | <?= e((string)$item['item_type']) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">Catalogue</a>
            <?php if ($permissionService->canEditBillableItems($currentUser)): ?>
                <a class="btn-secondary" href="edit.php?id=<?= (int)$item['id'] ?>">Edit</a>
            <?php endif; ?>
            <?php if ($permissionService->canManageBillableItemStatus($currentUser)): ?>
                <form method="post" action="action.php" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="action" value="<?= !empty($item['is_active']) ? 'deactivate' : 'activate' ?>">
                    <button class="btn-primary" type="submit"><?= !empty($item['is_active']) ? 'Deactivate' : 'Activate' ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Department</span><span class="summary-value"><?= e((string)($item['department_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Unit</span><span class="summary-value"><?= e((string)($item['unit'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Unit Price</span><span class="summary-value"><?= e(number_format((float)$item['unit_price'], 2)) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span><span class="summary-value"><?= !empty($item['is_active']) ? 'Active' : 'Inactive' ?></span></div>
            <div class="summary-item"><span class="summary-label">Created By</span><span class="summary-value"><?= e((string)($item['created_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Updated By</span><span class="summary-value"><?= e((string)($item['updated_by_name'] ?? '-')) ?></span></div>
        </div>
    </div>

    <div class="card">
        <h3>Description</h3>
        <p><?= nl2br(e((string)($item['description'] ?? ''))) ?></p>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>

