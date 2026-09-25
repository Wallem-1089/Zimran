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

if (!$permissionService->canApplyBillingDiscount($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to apply billing discounts.');
}

if (!$billingTablesReady || !$billingDiscountsReady) {
    http_response_code(503);
    exit('Billing discount tables are not available yet. Apply Migration 072 to enable discounts.');
}

$invoice = $billingService->getInvoiceByVisit($visitId, $currentUser);
if (!$invoice) {
    $_SESSION['error_message'] = 'Create or refresh the invoice before applying a discount.';
    header('Location: view.php?visit=' . $visitId);
    exit;
}

$summary = $billingService->getEncounterBalance($visitId, $currentUser);
$charges = $billingService->listChargesByVisit($visitId, $currentUser);
$activeCharges = array_values(array_filter($charges, static function (array $charge): bool {
    return (string)($charge['status'] ?? '') === 'Active';
}));
$patientName = billingDisplayPatientName($visit);

$pageTitle = 'Apply Billing Discount';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Apply Discount</h1>
            <p><?= e((string)$visit['visit_number']) ?> | <?= e($patientName) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="view.php?visit=<?= (int)$visitId ?>">Back to Billing</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['validation_errors'])): ?>
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

    <div class="card">
        <h2>Invoice Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Invoice</span>
                <span class="summary-value"><?= e((string)$invoice['invoice_number']) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Gross Charges</span>
                <span class="summary-value">&#8358;<?= e(number_format((float)($summary['total_charges'] ?? 0), 2)) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Charge Discounts</span>
                <span class="summary-value">&#8358;<?= e(number_format((float)($summary['charge_discounts'] ?? 0), 2)) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Subtotal After Line Discounts</span>
                <span class="summary-value">&#8358;<?= e(number_format((float)($summary['discounted_subtotal'] ?? 0), 2)) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Invoice Discounts</span>
                <span class="summary-value">&#8358;<?= e(number_format((float)($summary['invoice_discounts'] ?? 0), 2)) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Total Discounts</span>
                <span class="summary-value">&#8358;<?= e(number_format((float)($summary['total_discounts'] ?? 0), 2)) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Current Balance</span>
                <span class="summary-value">&#8358;<?= e(number_format((float)($summary['balance_due'] ?? 0), 2)) ?></span>
            </div>
        </div>
    </div>

    <form method="post" action="discount_save.php" class="card">
        <?= csrfField() ?>
        <input type="hidden" name="visit_id" value="<?= (int)$visitId ?>">
        <input type="hidden" name="invoice_id" value="<?= (int)$invoice['id'] ?>">

        <h2>Discount Details</h2>
        <p class="text-muted">Only preset discounts are allowed: 5%, 10%, or 15%. Original charges and Accounts prices are not changed.</p>

        <div class="form-grid">
            <div class="form-group">
                <label for="patient_charge_id">Applies To <span class="required">*</span></label>
                <select id="patient_charge_id" name="patient_charge_id">
                    <option value="">Whole invoice after line discounts</option>
                    <?php foreach ($activeCharges as $charge): ?>
                        <option value="<?= (int)$charge['id'] ?>">
                            Charge #<?= (int)$charge['id'] ?> -
                            <?= e((string)($charge['item_name'] ?? $charge['description'] ?? 'Charge')) ?>
                            (&#8358;<?= e((string)($charge['display_amount'] ?? '0.00')) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Choose a charge for a line discount, or leave as whole invoice for an overall discount.</small>
            </div>

            <div class="form-group">
                <label for="discount_percent">Discount Percentage <span class="required">*</span></label>
                <select id="discount_percent" name="discount_percent" required>
                    <option value="">Select Discount</option>
                    <option value="5">5%</option>
                    <option value="10">10%</option>
                    <option value="15">15%</option>
                </select>
            </div>

            <div class="form-group">
                <label for="reason">Reason / Authorization Note <span class="required">*</span></label>
                <textarea id="reason" name="reason" rows="4" required placeholder="Explain why this discount is approved."></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn-primary" type="submit">Apply Discount</button>
            <a class="btn-secondary" href="view.php?visit=<?= (int)$visitId ?>">Cancel</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
