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

if (!$permissionService->canViewBilling($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to view billing.');
}

if (!$billingTablesReady) {
    http_response_code(503);
    exit('Billing tables are not available yet. Apply Migration 033 to enable this section.');
}

$billingSummary = $billingTablesReady ? $billingService->getEncounterBalance($visitId, $currentUser) : ['success' => true, 'invoice' => null, 'total_charges' => 0, 'amount_paid' => 0, 'balance_due' => 0, 'status' => 'Unbilled', 'errors' => []];
$billingCharges = $billingTablesReady ? $billingService->listChargesByVisit($visitId, $currentUser) : [];
$billingPayments = $billingTablesReady ? $billingService->listPayments($visitId, $currentUser) : [];
$billingInvoice = $billingSummary['invoice'] ?? null;
$canCreatePatientCharge = $permissionService->canCreatePatientCharge($currentUser);
$canCancelPatientCharge = $permissionService->canCancelPatientCharge($currentUser);
$canCreateInvoice = $permissionService->canCreateInvoice($currentUser);
$canRecordPayment = $permissionService->canRecordPayment($currentUser);
$canViewReceipts = $permissionService->canViewReceipts($currentUser);
$billingPatientName = trim((string)($visit['patient_name'] ?? ''));
if ($billingPatientName === '') {
    $billingPatientName = trim((string)($visit['first_name'] ?? '') . ' ' . (string)($visit['last_name'] ?? ''));
}
if ($billingPatientName === '') {
    $billingPatientName = 'Unknown Patient';
}

$pageTitle = 'Billing';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Billing / Charges</h1>
            <p><?= e((string)$visit['visit_number']) ?> | <?= e($billingPatientName) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">Billing Home</a>
            <?php if ($canCreatePatientCharge): ?>
                <a class="btn-primary" href="charge_create.php?visit=<?= (int)$visit['id'] ?>">Add Charge</a>
            <?php endif; ?>
            <?php if ($canRecordPayment): ?>
                <a class="btn-primary" href="payment_create.php?visit=<?= (int)$visit['id'] ?>">Record Payment</a>
            <?php endif; ?>
        </div>
    </div>

    <?php require __DIR__ . '/_summary.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
