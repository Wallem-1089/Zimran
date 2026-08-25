<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
stockRequestRequireReady($stockRequestTablesReady);

if (!$permissionService->canIssueStockRequest($currentUser)) {
    http_response_code(403);
    exit('Stock request issue access denied.');
}

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$request = $stockRequestService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Stock request not found.');
}

$pageTitle = 'Issue Stock Request';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?><div class="alert-danger"><ul><?php foreach ((array)$_SESSION['validation_errors'] as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?></ul></div><?php unset($_SESSION['validation_errors']); endif; ?>
    <div class="page-header">
        <div><h1>Issue Stock Request #<?= (int)$request['id'] ?></h1><p><?= e((string)$request['requesting_department_name']) ?></p></div>
        <div><a class="btn-secondary" href="view.php?id=<?= (int)$request['id'] ?>">Back</a></div>
    </div>
    <form class="card" method="post" action="issue_save.php">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
        <p class="text-muted">Enter the quantity Store is issuing now. Leave a row as 0 to skip it.</p>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Item</th><th>Requested</th><th>Already Issued</th><th>Remaining</th><th>Issue Now</th></tr></thead>
                <tbody>
                <?php foreach (($request['items'] ?? []) as $item): $remaining = max(0, (float)$item['quantity_requested'] - (float)$item['quantity_issued']); ?>
                    <tr>
                        <td><?= e((string)$item['item_code']) ?> - <?= e((string)$item['item_name']) ?></td>
                        <td><?= e(number_format((float)$item['quantity_requested'], 2)) ?> <?= e((string)$item['unit']) ?></td>
                        <td><?= e(number_format((float)$item['quantity_issued'], 2)) ?> <?= e((string)$item['unit']) ?></td>
                        <td><?= e(number_format($remaining, 2)) ?> <?= e((string)$item['unit']) ?></td>
                        <td><input name="issue_quantity[<?= (int)$item['id'] ?>]" type="number" min="0" max="<?= e((string)$remaining) ?>" step="0.01" value="<?= $remaining > 0 ? e((string)$remaining) : '0' ?>"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Issue Stock</button>
            <a class="btn-secondary" href="view.php?id=<?= (int)$request['id'] ?>">Cancel</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
