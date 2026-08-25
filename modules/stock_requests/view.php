<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
stockRequestRequireReady($stockRequestTablesReady);
stockRequestRequireView($permissionService, $currentUser);

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$request = $stockRequestService->getRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Stock request not found.');
}

$canApprove = (string)$request['status'] === 'Pending' && $permissionService->canReviewStockRequest($currentUser);
$canIssue = in_array((string)$request['status'], ['Pending','Approved','Partially Issued'], true)
    && $permissionService->canIssueStockRequest($currentUser);
$canCancel = in_array((string)$request['status'], ['Pending','Approved','Partially Issued'], true)
    && $permissionService->canCancelStockRequest($currentUser);

$pageTitle = 'Stock Request';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['success_message'])): ?><div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div><?php unset($_SESSION['success_message']); endif; ?>
    <?php if (isset($_SESSION['validation_errors'])): ?><div class="alert-danger"><ul><?php foreach ((array)$_SESSION['validation_errors'] as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?></ul></div><?php unset($_SESSION['validation_errors']); endif; ?>

    <div class="page-header">
        <div>
            <h1>Stock Request #<?= (int)$request['id'] ?></h1>
            <p><?= e((string)$request['requesting_department_name']) ?> | <?= e((string)$request['status']) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print</button>
            <a class="btn-secondary" href="index.php">Stock Requests</a>
            <?php if ($canIssue): ?><a class="btn-primary" href="issue.php?id=<?= (int)$request['id'] ?>">Issue Stock</a><?php endif; ?>
            <?php if ($canApprove): ?>
                <form method="post" action="approve.php" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                    <button class="btn-secondary" type="submit">Approve</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)$request['requesting_department_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Requested By</span> <span class="summary-value"><?= e((string)($request['requested_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Created</span> <span class="summary-value"><?= e((string)($request['created_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Reviewed By</span> <span class="summary-value"><?= e((string)($request['reviewed_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Reviewed At</span> <span class="summary-value"><?= e((string)($request['reviewed_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$request['status']) ?></span></div>
        </div>
        <?php if (!empty($request['reason'])): ?>
            <h3>Reason</h3>
            <p><?= nl2br(e((string)$request['reason'])) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Items</h3>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Item</th><th>Requested</th><th>Issued</th><th>Remaining</th><th>Notes</th></tr></thead>
                <tbody>
                <?php foreach (($request['items'] ?? []) as $item): $remaining = (float)$item['quantity_requested'] - (float)$item['quantity_issued']; ?>
                    <tr>
                        <td><?= e((string)$item['item_code']) ?> - <?= e((string)$item['item_name']) ?></td>
                        <td><?= e(number_format((float)$item['quantity_requested'], 2)) ?> <?= e((string)$item['unit']) ?></td>
                        <td><?= e(number_format((float)$item['quantity_issued'], 2)) ?> <?= e((string)$item['unit']) ?></td>
                        <td><?= e(number_format(max(0, $remaining), 2)) ?> <?= e((string)$item['unit']) ?></td>
                        <td><?= e((string)($item['notes'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($canCancel): ?>
        <form class="card no-print" method="post" action="cancel.php" onsubmit="return confirm('Cancel this stock request?');">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
            <label>Cancellation Reason
                <textarea name="reason" required maxlength="2000"></textarea>
            </label>
            <button class="btn-danger" type="submit">Cancel Request</button>
        </form>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
