<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$itemId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if (!$accountsTablesReady) {
    http_response_code(503);
    exit('Accounts tables are not available yet. Apply Migration 030 to enable this section.');
}

if (!$permissionService->canEditBillableItems($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to edit billable items.');
}

$item = $accountsService->getItemById($itemId, $currentUser);
if (!$item) {
    http_response_code(404);
    exit('Billable item not found.');
}

if (trim((string)($_SESSION['error_message'] ?? '')) !== '') {
    // no-op; preserve any existing error message
}

if (isset($_SESSION['old_billable_item']) && is_array($_SESSION['old_billable_item'])) {
    $item = array_merge($item, $_SESSION['old_billable_item']);
    unset($_SESSION['old_billable_item']);
}

$pageTitle = 'Edit Billable Item';
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
            <ul>
                <?php foreach ((array)$_SESSION['validation_errors'] as $error): ?>
                    <li><?= e((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>
    <div class="page-header">
        <div>
            <h1>Edit Billable Item</h1>
            <p><?= e((string)$item['item_code']) ?></p>
        </div>
        <div><a class="btn-secondary" href="view.php?id=<?= (int)$item['id'] ?>">Back</a></div>
    </div>
    <?php $departments = $accountsDepartmentOptions; $action = 'update.php'; $buttonLabel = 'Update Item'; require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>

