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
$billingRequests = $billingRequestsReady && $billingTablesReady ? $billingService->listBillingRequests(['visit_id' => $visitId], $currentUser) : [];
$billingDiscounts = $billingDiscountsReady && $billingTablesReady ? $billingService->listDiscountsByVisit($visitId, $currentUser) : [];
$billingInvoice = $billingSummary['invoice'] ?? null;
$activeDepartmentName = (string)($currentUser['active_department_name'] ?? $currentUser['department_name'] ?? '');
$canCreatePatientCharge = $permissionService->canCreatePatientCharge($currentUser)
    && strcasecmp($activeDepartmentName, 'Accounts') !== 0;
$canCancelPatientCharge = $permissionService->canCancelPatientCharge($currentUser);
$canCreateBillingRequest = $permissionService->canCreateBillingRequest($currentUser);
$canViewBillingRequests = $permissionService->canViewBillingRequests($currentUser);
$canReviewBillingRequest = $permissionService->canReviewBillingRequest($currentUser);
$canCancelBillingRequest = $permissionService->canCancelBillingRequest($currentUser);
$canCreateInvoice = $permissionService->canCreateInvoice($currentUser);
$canRecordPayment = $permissionService->canRecordPayment($currentUser);
$canViewReceipts = $permissionService->canViewReceipts($currentUser);
$canCancelPayment = $permissionService->isAdministrator($currentUser);
$canViewBillingDiscounts = $permissionService->canViewBillingDiscounts($currentUser);
$canApplyBillingDiscount = $permissionService->canApplyBillingDiscount($currentUser);
$canCancelBillingDiscount = $permissionService->canCancelBillingDiscount($currentUser);
$billingShowFullHistory = (string)($_GET['history'] ?? '') === 'full';
$patient = [
    'id' => (int)($visit['patient_id'] ?? 0),
    'hospital_number' => (string)($visit['hospital_number'] ?? ''),
    'first_name' => (string)($visit['first_name'] ?? ''),
    'last_name' => (string)($visit['last_name'] ?? ''),
    'gender' => (string)($visit['gender'] ?? ''),
    'phone' => (string)($visit['phone'] ?? ''),
    'date_of_birth' => (string)($visit['date_of_birth'] ?? ''),
];
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
    <style>
        .invoice-print { display: none; }
        @media print {
            @page { size: auto; margin: 10mm; }
            .billing-screen { display: none !important; }
            .invoice-print { display: block !important; width: 100%; max-width: 100%; box-sizing: border-box; overflow: visible; }
            .invoice-print h1 { margin: 0 0 1.25rem; }
            .invoice-print .print-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem 2rem; margin-bottom: 1.5rem; }
            .invoice-print .print-meta div { border-bottom: 1px solid #d1d5db; padding: .45rem 0; }
            .invoice-print .print-meta strong { display: inline-block; min-width: 8rem; }
            .invoice-print table { width: 100%; max-width: 100%; table-layout: fixed; border-collapse: collapse; }
            .invoice-print th:first-child, .invoice-print td:first-child { width: 70%; overflow-wrap: anywhere; }
            .invoice-print th:last-child, .invoice-print td:last-child { width: 30%; white-space: nowrap; }
            .invoice-print th, .invoice-print td { border: 1px solid #9ca3af; padding: .6rem; text-align: left; }
            .invoice-print .total-paid { font-size: 1.15rem; font-weight: 700; }
        }
    </style>
    <div class="billing-screen">
    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert-danger"><?= e((string)$_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
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

    <div class="page-header">
        <div>
            <h1>Billing / Charges</h1>
            <p><?= e((string)$visit['visit_number']) ?> | <?= e($billingPatientName) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Invoice</button>
            <a class="btn-secondary" href="index.php">Billing Home</a>
            <?php if ($canCreateBillingRequest): ?>
                <a class="btn-secondary" href="request_create.php?visit=<?= (int)$visit['id'] ?>">Request Billing</a>
            <?php endif; ?>
            <?php if ($canViewBillingRequests): ?>
                <a class="btn-secondary" href="billing_requests.php">Billing Requests</a>
            <?php endif; ?>
            <?php if ($canCreatePatientCharge): ?>
                <a class="btn-primary" href="charge_create.php?visit=<?= (int)$visit['id'] ?>">Add Charge</a>
            <?php endif; ?>
            <?php if ($canRecordPayment): ?>
                <a class="btn-primary" href="payment_create.php?visit=<?= (int)$visit['id'] ?>">Record Payment</a>
            <?php endif; ?>
            <?php if ($canApplyBillingDiscount && $billingInvoice): ?>
                <a class="btn-secondary" href="discount_create.php?visit=<?= (int)$visit['id'] ?>">Apply Discount</a>
            <?php endif; ?>
        </div>
    </div>

    <?php require __DIR__ . '/_summary.php'; ?>
    </div>

    <section class="invoice-print" aria-label="Printable invoice">
        <h1>Invoice</h1>
        <div class="print-meta">
            <div><strong>Hospital Number</strong> <?= e((string)($patient['hospital_number'] ?? '-')) ?></div>
            <div><strong>Invoice Number</strong> <?= e((string)($billingInvoice['invoice_number'] ?? '-')) ?></div>
            <div><strong>Name of Patient</strong> <?= e($billingPatientName) ?></div>
            <div><strong>Discount</strong> &#8358;<?= e(number_format($billingDiscountsTotal, 2)) ?></div>
            <div class="total-paid"><strong>Total Price Paid</strong> &#8358;<?= e(number_format((float)($billingInvoice['amount_paid'] ?? $billingPaymentsTotal), 2)) ?></div>
        </div>
        <table>
            <thead>
                <tr><th>Service or Product Paid For</th><th>Price Paid</th></tr>
            </thead>
            <tbody>
                <?php $printChargeCount = 0; ?>
                <?php foreach ($billingCharges as $charge): ?>
                    <?php if ((string)($charge['status'] ?? 'Active') === 'Cancelled') { continue; } ?>
                    <?php
                        $printChargeCount++;
                        $printChargeId = (int)($charge['id'] ?? 0);
                        $printDiscount = (float)($billingDiscountsByCharge[$printChargeId] ?? 0);
                        $printAmount = max(0, (float)($charge['amount'] ?? 0) - $printDiscount);
                    ?>
                    <tr>
                        <td><?= e((string)($charge['item_name'] ?? $charge['description'] ?? '-')) ?></td>
                        <td>&#8358;<?= e(number_format($printAmount, 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($printChargeCount === 0): ?><tr><td colspan="2">No services or products recorded.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
