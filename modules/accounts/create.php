<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$accountsTablesReady) {
    http_response_code(503);
    exit('Accounts tables are not available yet. Apply Migration 030 to enable this section.');
}

if (!$permissionService->canCreateBillableItems($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to create billable items.');
}

$item = $_SESSION['old_billable_item'] ?? [];
unset($_SESSION['old_billable_item']);

$pageTitle = 'Create Price Catalogue Item';
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
            <h1>Create Price Catalogue Item</h1>
            <p>Standalone hospital price master data.</p>
        </div>
        <div><a class="btn-secondary" href="index.php">Back to Catalogue</a></div>
    </div>
    <?php $departments = $accountsDepartmentOptions; $action = 'save.php'; $buttonLabel = 'Save Item'; require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>

