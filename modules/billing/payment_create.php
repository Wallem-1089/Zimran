<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
if ($visitId <= 0) {
    http_response_code(400);
    exit('Visit is required.');
}

$visit = $visitService->getVisitById($visitId);
if (!$visit) {
    http_response_code(404);
    exit('Encounter not found.');
}

$billingPatientName = billingDisplayPatientName($visit);

if (!$permissionService->canRecordPayment($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to record payments.');
}

if (!$billingTablesReady) {
    http_response_code(503);
    exit('Billing tables are not available yet. Apply Migration 033 to enable this section.');
}

$invoice = $billingTablesReady ? $billingService->getInvoiceByVisit($visitId, $currentUser) : null;
$billingSummary = $billingTablesReady ? $billingService->getEncounterBalance($visitId, $currentUser) : ['success' => true, 'invoice' => null, 'total_charges' => 0, 'amount_paid' => 0, 'balance_due' => 0, 'status' => 'Unbilled', 'errors' => []];
$billingCharges = $billingTablesReady ? $billingService->listChargesByVisit($visitId, $currentUser) : [];
$billingPayments = $billingTablesReady ? $billingService->listPayments($visitId, $currentUser) : [];
$billingInvoice = $billingSummary['invoice'] ?? null;

$pageTitle = 'Record Payment';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Record Payment</h1>
            <p><?= e((string)$visit['visit_number']) ?> | <?= e($billingPatientName) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="view.php?visit=<?= (int)$visit['id'] ?>">Back to Billing</a>
        </div>
    </div>

    <?php require __DIR__ . '/_summary.php'; ?>

    <div class="card">
        <h3>New Payment</h3>
        <?php if (!$invoice): ?>
            <div class="empty-state">Create an invoice before recording a payment.</div>
        <?php else: ?>
            <form method="post" action="payment_save.php" class="form-grid">
                <?= csrfField() ?>
                <input type="hidden" name="invoice_id" value="<?= (int)$invoice['id'] ?>">
                <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input id="amount" name="amount" type="number" min="0.01" step="0.01" value="<?= e((string)max(0.01, (float)$invoice['balance_due'])) ?>" required>
                </div>
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <?php foreach (['Cash', 'Card', 'Transfer', 'Other'] as $method): ?>
                            <option value="<?= e($method) ?>"><?= e($method) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reference">Reference</label>
                    <input id="reference" name="reference" placeholder="Optional receipt / transaction reference">
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Optional payment note"></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn-primary" type="submit">Save Payment</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
