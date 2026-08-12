<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$paymentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($paymentId <= 0) {
    http_response_code(400);
    exit('Receipt is required.');
}

if (!$billingTablesReady) {
    http_response_code(503);
    exit('Billing tables are not available yet. Apply Migration 033 to enable this section.');
}

$receipt = $billingService->getReceiptData($paymentId, $currentUser);
if (!$receipt) {
    http_response_code(404);
    exit('Receipt not found.');
}

$pageTitle = 'Receipt';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Receipt</h1>
            <p>Payment #<?= (int)$receipt['id'] ?> | Invoice <?= e((string)$receipt['invoice_number']) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print</button>
            <a class="btn-secondary" href="view.php?visit=<?= (int)$receipt['visit_id'] ?>">Back to Billing</a>
        </div>
    </div>

    <div class="card">
        <h2>Hospital Management System</h2>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Receipt No.</span><span class="summary-value">RCPT-<?= (int)$receipt['id'] ?></span></div>
            <div class="summary-item"><span class="summary-label">Patient</span><span class="summary-value"><?= e((string)($receipt['patient_name'] ?? '—')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span><span class="summary-value"><?= e((string)($receipt['hospital_number'] ?? '—')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Visit Number</span><span class="summary-value"><?= e((string)($receipt['visit_number'] ?? '—')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Invoice Number</span><span class="summary-value"><?= e((string)$receipt['invoice_number']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Amount Paid</span><span class="summary-value">₦<?= e(number_format((float)$receipt['amount'], 2)) ?></span></div>
            <div class="summary-item"><span class="summary-label">Payment Method</span><span class="summary-value"><?= e((string)$receipt['payment_method']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Received By</span><span class="summary-value"><?= e((string)($receipt['received_by_name'] ?? '—')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Date</span><span class="summary-value"><?= e((string)$receipt['created_at']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Remaining Balance</span><span class="summary-value">₦<?= e(number_format((float)$receipt['invoice_balance_due'], 2)) ?></span></div>
        </div>
        <?php if (!empty($receipt['notes'])): ?>
            <p><strong>Notes:</strong> <?= nl2br(e((string)$receipt['notes'])) ?></p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
