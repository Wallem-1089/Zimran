<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$permissionService->canReviewBillingRequest($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to review billing requests.');
}

if (!$billingTablesReady || !$billingRequestsReady) {
    http_response_code(503);
    exit('Billing request tables are not available yet. Apply Migration 044 to enable this section.');
}

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$request = $billingService->getBillingRequestById($requestId, $currentUser);
if (!$request) {
    http_response_code(404);
    exit('Billing request not found.');
}

$items = $accountsService->searchItems(['status' => 'active'], $currentUser);
$items = array_values(array_filter($items, static fn (array $item): bool => !empty($item['is_active'])));
$suggestedId = (int)($request['suggested_billable_item_id'] ?? 0);

$pageTitle = 'Review Billing Request';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Review Billing Request</h1>
            <p><?= e((string)($request['visit_number'] ?? ('#' . (int)$request['visit_id']))) ?> | <?= e((string)($request['patient_name'] ?? 'Unknown Patient')) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="billing_requests.php">Billing Requests</a>
            <a class="btn-secondary" href="view.php?visit=<?= (int)$request['visit_id'] ?>">Open Billing</a>
        </div>
    </div>

    <div class="card">
        <h3>Department Recommendation</h3>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)($request['department_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Source</span> <span class="summary-value"><?= e((string)($request['source_module'] ?? 'General')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)($request['status'] ?? 'Pending')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Requested By</span> <span class="summary-value"><?= e((string)($request['requested_by_name'] ?? '-')) ?></span></div>
        </div>
        <p><?= nl2br(e((string)($request['description'] ?? ''))) ?></p>
    </div>

    <?php if ((string)($request['status'] ?? '') !== 'Pending'): ?>
        <div class="card">
            <div class="empty-state">This billing request is already <?= e((string)$request['status']) ?>.</div>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Create Official Charge</h3>
            <form method="post" action="request_charge_save.php" class="form-grid">
                <?= csrfField() ?>
                <input type="hidden" name="billing_request_id" value="<?= (int)$request['id'] ?>">
                <div class="form-group">
                    <label for="billable_item_id">Billable Item</label>
                    <select id="billable_item_id" name="billable_item_id" required>
                        <option value="">Select official item</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int)$item['id'] ?>" <?= $suggestedId === (int)$item['id'] ? 'selected' : '' ?>>
                                <?= e((string)$item['item_code']) ?> — <?= e((string)$item['item_name']) ?> (&#8358;<?= e(number_format((float)$item['unit_price'], 2)) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="0.01" step="0.01" value="<?= e((string)($request['display_quantity'] ?? '1')) ?>" required>
                </div>
                <div class="form-group full-width">
                    <label for="description">Charge Description</label>
                    <textarea id="description" name="description" rows="3"><?= e((string)($request['description'] ?? '')) ?></textarea>
                </div>
                <div class="form-group full-width">
                    <label for="notes">Review Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Optional Accounts review note"></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn-primary" type="submit">Create Patient Charge</button>
                </div>
            </form>

            <?php if ($permissionService->canCancelBillingRequest($currentUser)): ?>
                <form method="post" action="request_cancel.php" class="form-grid" style="margin-top:1rem;">
                    <?= csrfField() ?>
                    <input type="hidden" name="billing_request_id" value="<?= (int)$request['id'] ?>">
                    <input type="hidden" name="visit_id" value="<?= (int)$request['visit_id'] ?>">
                    <div class="form-group full-width">
                        <label for="reason">Cancel Reason</label>
                        <textarea id="reason" name="reason" rows="2" required></textarea>
                    </div>
                    <div class="form-actions">
                        <button class="btn-secondary" type="submit">Cancel Billing Request</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
