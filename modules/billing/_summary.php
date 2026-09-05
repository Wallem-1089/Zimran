<?php

declare(strict_types=1);

if (!isset($visit, $patient, $billingSummary, $billingCharges, $billingPayments)) {
    return;
}

$billingInvoice = $billingSummary['invoice'] ?? null;
$billingChargesTotal = (float)($billingSummary['total_charges'] ?? 0);
$billingPaymentsTotal = (float)($billingSummary['amount_paid'] ?? 0);
$billingBalanceDue = (float)($billingSummary['balance_due'] ?? 0);
$billingStatus = (string)($billingSummary['status'] ?? 'Unbilled');
$billingRequests = $billingRequests ?? [];
$billingRequestsReady = $billingRequestsReady ?? false;
$billingShowFullHistory = !empty($billingShowFullHistory) || (string)($_GET['history'] ?? '') === 'full';
$billingRequestRows = $billingShowFullHistory ? $billingRequests : array_slice($billingRequests, 0, 10);
$billingChargeRows = $billingShowFullHistory ? $billingCharges : array_slice($billingCharges, 0, 15);
$billingPaymentRows = $billingShowFullHistory ? $billingPayments : array_slice($billingPayments, 0, 10);
$billingFullHistoryUrl = '../billing/view.php?visit=' . (int)$visit['id'] . '&history=full';
$billingRecentUrl = '../billing/view.php?visit=' . (int)$visit['id'];
?>

<div class="card">
    <div class="card-header">
        <div>
            <h2>Billing & Accounts</h2>
            <p>Charges, invoices, payments, and receipts for this encounter.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="../billing/view.php?visit=<?= (int)$visit['id'] ?>">Open Billing</a>
            <?php if (!empty($canCreateBillingRequest)): ?>
                <a class="btn-secondary" href="../billing/request_create.php?visit=<?= (int)$visit['id'] ?>">Request Billing</a>
            <?php endif; ?>
            <?php if (!empty($canViewBillingRequests)): ?>
                <a class="btn-secondary" href="../billing/billing_requests.php">Billing Requests</a>
            <?php endif; ?>
            <?php if (!empty($canCreatePatientCharge)): ?>
                <a class="btn-primary" href="../billing/charge_create.php?visit=<?= (int)$visit['id'] ?>">Add Charge</a>
            <?php endif; ?>
            <?php if (!empty($canRecordPayment)): ?>
                <a class="btn-primary" href="../billing/payment_create.php?visit=<?= (int)$visit['id'] ?>">Record Payment</a>
            <?php endif; ?>
            <?php if ($billingShowFullHistory): ?>
                <a class="btn-secondary" href="<?= e($billingRecentUrl) ?>">Show Recent</a>
            <?php else: ?>
                <a class="btn-secondary" href="<?= e($billingFullHistoryUrl) ?>">Full History</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <span class="summary-label">Encounter</span>
            <span class="summary-value">#<?= (int)$visit['id'] ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Hospital Number</span>
            <span class="summary-value"><?= e((string)$patient['hospital_number']) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Invoice</span>
            <span class="summary-value"><?= e((string)($billingInvoice['invoice_number'] ?? '-')) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Charges</span>
            <span class="summary-value">&#8358;<?= e(number_format($billingChargesTotal, 2)) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Payments</span>
            <span class="summary-value">&#8358;<?= e(number_format($billingPaymentsTotal, 2)) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Outstanding Balance</span>
            <span class="summary-value">&#8358;<?= e(number_format($billingBalanceDue, 2)) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Invoice Status</span>
            <span class="summary-value"><?= e($billingInvoice['status'] ?? $billingStatus) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Payment Status</span>
            <span class="summary-value"><?= e($billingBalanceDue <= 0 && $billingChargesTotal > 0 ? 'Paid' : ($billingChargesTotal > 0 ? 'Open' : 'No Charges')) ?></span>
        </div>
    </div>
</div>

<div class="card">
    <div class="section-header">
        <div>
            <h3>Billing Requests</h3>
            <?php if (count($billingRequests) > count($billingRequestRows)): ?>
                <p class="text-muted">Showing latest <?= count($billingRequestRows) ?> of <?= count($billingRequests) ?> requests.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!$billingRequestsReady): ?>
        <div class="empty-state">Billing request tables are not available yet. Apply Migration 044 to enable recommendations.</div>
    <?php elseif (empty($billingRequests)): ?>
        <div class="empty-state">No billing requests for this encounter.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Department</th>
                        <th>Suggested Item</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($billingRequestRows as $request): ?>
                        <tr>
                            <td><?= e((string)($request['description'] ?? '-')) ?></td>
                            <td><?= e((string)($request['department_name'] ?? '-')) ?></td>
                            <td><?= e((string)($request['suggested_item_name'] ?? '-')) ?></td>
                            <td><?= e((string)($request['display_quantity'] ?? '1')) ?></td>
                            <td><?= e((string)($request['status'] ?? 'Pending')) ?></td>
                            <td><?= e((string)($request['requested_by_name'] ?? '-')) ?></td>
                            <td>
                                <?php if (!empty($canReviewBillingRequest) && (string)($request['status'] ?? '') === 'Pending'): ?>
                                    <a class="btn-primary btn-sm" href="../billing/request_review.php?id=<?= (int)$request['id'] ?>">Create Charge</a>
                                <?php endif; ?>
                                <?php if (!empty($canCancelBillingRequest) && (string)($request['status'] ?? '') === 'Pending'): ?>
                                    <form method="post" action="../billing/request_cancel.php" style="display:inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="billing_request_id" value="<?= (int)$request['id'] ?>">
                                        <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
                                        <input type="hidden" name="reason" value="Cancelled from billing workspace.">
                                        <button class="btn-secondary btn-sm" type="submit">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($billingRequests) > count($billingRequestRows)): ?>
            <div class="form-actions">
                <a class="btn-secondary" href="<?= e($billingFullHistoryUrl) ?>">View Full Billing Request History</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Invoice</h3>
    <?php if ($billingInvoice): ?>
        <table class="summary-table">
            <tbody>
                <tr><th>Invoice Number</th><td><?= e((string)$billingInvoice['invoice_number']) ?></td></tr>
                <tr><th>Status</th><td><?= e((string)$billingInvoice['status']) ?></td></tr>
                <tr><th>Total</th><td>&#8358;<?= e(number_format((float)$billingInvoice['total_amount'], 2)) ?></td></tr>
                <tr><th>Paid</th><td>&#8358;<?= e(number_format((float)$billingInvoice['amount_paid'], 2)) ?></td></tr>
                <tr><th>Balance</th><td>&#8358;<?= e(number_format((float)$billingInvoice['balance_due'], 2)) ?></td></tr>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">No invoice has been generated for this encounter.</div>
    <?php endif; ?>

    <div class="form-actions" style="margin-top: 1rem;">
        <?php if (!empty($canCreateInvoice)): ?>
                <form method="post" action="../billing/invoice_save.php">
                <?= csrfField() ?>
                <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
                <button class="btn-primary" type="submit"><?= $billingInvoice ? 'Refresh Invoice Totals' : 'Create Invoice' ?></button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="section-header">
        <div>
            <h3>Charges</h3>
            <?php if (count($billingCharges) > count($billingChargeRows)): ?>
                <p class="text-muted">Showing latest <?= count($billingChargeRows) ?> of <?= count($billingCharges) ?> charges.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php if (empty($billingCharges)): ?>
        <div class="empty-state">No billable services recorded.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($billingChargeRows as $charge): ?>
                        <tr>
                            <td><?= e((string)($charge['item_name'] ?? '-')) ?></td>
                            <td><?= e((string)($charge['display_quantity'] ?? '0')) ?></td>
                            <td>&#8358;<?= e((string)($charge['display_unit_price'] ?? '0.00')) ?></td>
                            <td>&#8358;<?= e((string)($charge['display_amount'] ?? '0.00')) ?></td>
                            <td><?= e((string)($charge['source_module'] ?? 'Billing')) ?></td>
                            <td><?= e((string)($charge['status'] ?? 'Active')) ?></td>
                            <td>
                                <a class="btn-secondary btn-sm" href="../billing/view.php?visit=<?= (int)$visit['id'] ?>#charge-<?= (int)$charge['id'] ?>">View</a>
                                <?php if (!empty($canCancelPatientCharge) && (string)($charge['status'] ?? '') === 'Active'): ?>
                                    <form method="post" action="../billing/charge_cancel.php" style="display:inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="charge_id" value="<?= (int)$charge['id'] ?>">
                                        <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
                                        <button class="btn-secondary btn-sm" type="submit">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($billingCharges) > count($billingChargeRows)): ?>
            <div class="form-actions">
                <a class="btn-secondary" href="<?= e($billingFullHistoryUrl) ?>">View Full Charge History</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="card" id="payments">
    <div class="section-header">
        <div>
            <h3>Payments</h3>
            <?php if (count($billingPayments) > count($billingPaymentRows)): ?>
                <p class="text-muted">Showing latest <?= count($billingPaymentRows) ?> of <?= count($billingPayments) ?> payments.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php if (empty($billingPayments)): ?>
        <div class="empty-state">No payments have been recorded.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Received By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($billingPaymentRows as $payment): ?>
                        <tr>
                            <td>#<?= (int)$payment['id'] ?></td>
                            <td><?= e((string)($payment['created_at'] ?? '-')) ?></td>
                            <td>&#8358;<?= e((string)($payment['display_amount'] ?? '0.00')) ?></td>
                            <td><?= e((string)($payment['payment_method'] ?? '-')) ?></td>
                            <td><?= e((string)($payment['received_by_name'] ?? '-')) ?></td>
                            <td>
                                <?php if (!empty($canViewReceipts)): ?>
                                        <a class="btn-secondary btn-sm" href="../billing/receipt.php?id=<?= (int)$payment['id'] ?>">Receipt</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($billingPayments) > count($billingPaymentRows)): ?>
            <div class="form-actions">
                <a class="btn-secondary" href="<?= e($billingFullHistoryUrl) ?>">View Full Payment History</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

