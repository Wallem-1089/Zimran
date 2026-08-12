<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

billingRequireAccess($permissionService, $currentUser);

if (!$billingTablesReady) {
    http_response_code(503);
    exit('Billing tables are not available yet. Apply Migration 033 to enable this section.');
}

$filters = [
    'invoice_number' => trim((string)($_GET['invoice_number'] ?? '')),
    'patient_name' => trim((string)($_GET['patient_name'] ?? '')),
    'hospital_number' => trim((string)($_GET['hospital_number'] ?? '')),
    'visit_number' => trim((string)($_GET['visit_number'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
];

$invoices = $billingTablesReady ? $billingService->listInvoices($filters, $currentUser) : [];
$recentPayments = $billingTablesReady ? $billingService->listPayments(null, $currentUser) : [];

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
            <h1>Billing</h1>
            <p>Patient Accounts, invoices, and payments.</p>
        </div>
    </div>

    <form method="get" class="card">
        <div class="form-grid">
            <div class="form-group">
                <label for="invoice_number">Invoice Number</label>
                <input id="invoice_number" name="invoice_number" value="<?= e($filters['invoice_number']) ?>">
            </div>
            <div class="form-group">
                <label for="patient_name">Patient Name</label>
                <input id="patient_name" name="patient_name" value="<?= e($filters['patient_name']) ?>">
            </div>
            <div class="form-group">
                <label for="hospital_number">Hospital Number</label>
                <input id="hospital_number" name="hospital_number" value="<?= e($filters['hospital_number']) ?>">
            </div>
            <div class="form-group">
                <label for="visit_number">Visit Number</label>
                <input id="visit_number" name="visit_number" value="<?= e($filters['visit_number']) ?>">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All</option>
                    <?php foreach (['Unpaid', 'Partially Paid', 'Paid', 'Cancelled'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Search</button>
            <a class="btn-secondary" href="index.php">Reset</a>
        </div>
    </form>

    <div class="card">
        <h3>Open Invoices</h3>
        <?php if ($invoices === []): ?>
            <div class="empty-state">No invoices found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Patient</th>
                            <th>Visit</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?= e((string)$invoice['invoice_number']) ?></td>
                                <td><?= e((string)($invoice['patient_name'] ?? '-')) ?></td>
                                <td>#<?= (int)$invoice['visit_id'] ?></td>
                                <td>₦<?= e((string)$invoice['display_total_amount']) ?></td>
                                <td>₦<?= e((string)$invoice['display_amount_paid']) ?></td>
                                <td>₦<?= e((string)$invoice['display_balance_due']) ?></td>
                                <td><?= e((string)$invoice['status']) ?></td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="view.php?visit=<?= (int)$invoice['visit_id'] ?>">Open</a>
                                    <?php if ($permissionService->canViewReceipts($currentUser) && (float)$invoice['amount_paid'] > 0): ?>
                                        <a class="btn-secondary btn-sm" href="receipt.php?id=<?= (int)$invoice['id'] ?>">Receipts</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Recent Payments</h3>
        <?php if ($recentPayments === []): ?>
            <div class="empty-state">No payments recorded yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Receipt</th>
                            <th>Patient</th>
                            <th>Visit</th>
                            <th>Invoice</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recentPayments, 0, 20) as $payment): ?>
                            <tr>
                                <td>#<?= (int)$payment['id'] ?></td>
                                <td><?= e((string)($payment['patient_name'] ?? '-')) ?></td>
                                <td>#<?= (int)$payment['visit_id'] ?></td>
                                <td><?= e((string)$payment['invoice_number']) ?></td>
                                <td>₦<?= e((string)$payment['display_amount']) ?></td>
                                <td><?= e((string)$payment['payment_method']) ?></td>
                                <td><?= e((string)$payment['created_at']) ?></td>
                                <td>
                                    <?php if ($permissionService->canViewReceipts($currentUser)): ?>
                                        <a class="btn-secondary btn-sm" href="receipt.php?id=<?= (int)$payment['id'] ?>">Receipt</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
