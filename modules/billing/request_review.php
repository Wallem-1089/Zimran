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
$canCancelThisRequest = $billingService->canCancelBillingRequestRow($request, $currentUser);

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
            <div class="summary-item"><span class="summary-label">Suggested Item</span> <span class="summary-value"><?= e((string)($request['suggested_item_name'] ?? 'Not set')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Charged Item</span> <span class="summary-value"><?= e((string)($request['charged_item_name'] ?? 'Not charged')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Patient Charge</span> <span class="summary-value"><?= !empty($request['patient_charge_id']) ? '#' . (int)$request['patient_charge_id'] : 'Pending automatic charge' ?></span></div>
            <div class="summary-item"><span class="summary-label">Requested By</span> <span class="summary-value"><?= e((string)($request['requested_by_name'] ?? '-')) ?></span></div>
        </div>
        <p><?= nl2br(e((string)($request['description'] ?? ''))) ?></p>
        <?php if (!empty($request['notes'])): ?>
            <p class="text-muted"><?= nl2br(e((string)$request['notes'])) ?></p>
        <?php endif; ?>
    </div>

    <?php if ((string)($request['status'] ?? '') !== 'Pending'): ?>
        <div class="card">
            <div class="empty-state">This billing request is already <?= e((string)$request['status']) ?>.</div>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Automatic Charge Pending</h3>
            <p class="text-muted">
                Accounts no longer creates this charge manually. Add a suggested billable item to the request source,
                or keep one clear active price catalogue item for this department/source, then recreate the request.
            </p>

            <?php if ($canCancelThisRequest): ?>
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
