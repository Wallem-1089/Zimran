<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$storeTablesReady) {
    http_response_code(503);
    exit('Store tables are not available yet. Apply Migration 031 to enable this section.');
}

storeRequireMovementAccess($permissionService, $currentUser, 'issue');

$itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$item = storeRequireItem($storeService, $itemId, $currentUser);
$movement = $_SESSION['old_store_movement'] ?? ['quantity' => '', 'reference' => '', 'remarks' => ''];
unset($_SESSION['old_store_movement']);

$pageTitle = 'Issue Stock';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?>
        <div class="alert-danger">
            <strong>Please correct the following:</strong>
            <ul><?php foreach ((array)$_SESSION['validation_errors'] as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?></ul>
        </div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>
    <div class="page-header">
        <div><h1>Issue Stock</h1><p><?= e((string)$item['item_code']) ?> — <?= e((string)$item['item_name']) ?></p></div>
        <div><a class="btn-secondary" href="<?= e(storeBackToView($itemId)) ?>">Back</a></div>
    </div>
    <?php $departments = $storeDepartmentOptions; $action = 'issue_save.php'; $buttonLabel = 'Issue'; $movementType = 'issue'; require __DIR__ . '/_stock_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>

