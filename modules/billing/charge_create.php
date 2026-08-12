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

if (!$permissionService->canCreatePatientCharge($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to create patient charges.');
}

if (!$billingTablesReady) {
    http_response_code(503);
    exit('Billing tables are not available yet. Apply Migration 033 to enable this section.');
}

$billingSummary = $billingTablesReady ? $billingService->getEncounterBalance($visitId, $currentUser) : ['success' => true, 'invoice' => null, 'total_charges' => 0, 'amount_paid' => 0, 'balance_due' => 0, 'status' => 'Unbilled', 'errors' => []];
$billingCharges = $billingTablesReady ? $billingService->listChargesByVisit($visitId, $currentUser) : [];
$billingPayments = $billingTablesReady ? $billingService->listPayments($visitId, $currentUser) : [];
$billingInvoice = $billingSummary['invoice'] ?? null;
$items = $accountsService->searchItems(['status' => 'active'], $currentUser);
$items = array_values(array_filter($items, static fn (array $item): bool => !empty($item['is_active'])));

$pageTitle = 'Add Patient Charge';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Add Patient Charge</h1>
            <p><?= e((string)$visit['visit_number']) ?> | <?= e(billingDisplayPatientName($visit)) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="view.php?visit=<?= (int)$visit['id'] ?>">Back to Billing</a>
        </div>
    </div>

    <?php require __DIR__ . '/_summary.php'; ?>

    <div class="card">
        <h3>New Charge</h3>
        <?php if ($items === []): ?>
            <div class="empty-state">No active billable items are available.</div>
        <?php else: ?>
            <form method="post" action="charge_save.php" class="form-grid">
                <?= csrfField() ?>
                <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
                <input type="hidden" name="source_module" value="Billing">
                <div class="form-group">
                    <label for="billable_item_id">Billable Item</label>
                    <select id="billable_item_id" name="billable_item_id" required>
                        <option value="">Select item</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int)$item['id'] ?>">
                                <?= e((string)$item['item_code']) ?> — <?= e((string)$item['item_name']) ?> (₦<?= e(number_format((float)$item['unit_price'], 2)) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="0.01" step="0.01" value="1" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" placeholder="Optional charge note"></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn-primary" type="submit">Save Charge</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
