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
$billingDiscounts = $billingDiscounts ?? [];
$billingDiscountsReady = $billingDiscountsReady ?? false;
$billingDiscountsTotal = (float)($billingSummary['total_discounts'] ?? 0);
$billingChargeDiscountsTotal = (float)($billingSummary['charge_discounts'] ?? 0);
$billingDiscountedSubtotal = (float)($billingSummary['discounted_subtotal'] ?? max(0, $billingChargesTotal - $billingChargeDiscountsTotal));
$billingInvoiceDiscountsTotal = (float)($billingSummary['invoice_discounts'] ?? max(0, $billingDiscountsTotal - $billingChargeDiscountsTotal));
$billingInvoiceTotal = (float)($billingSummary['invoice_total'] ?? max(0, $billingChargesTotal - $billingDiscountsTotal));
$billingRequests = $billingRequests ?? [];
$billingRequestsReady = $billingRequestsReady ?? false;
$billingShowFullHistory = !empty($billingShowFullHistory) || (string)($_GET['history'] ?? '') === 'full';
$billingRequestRows = $billingShowFullHistory ? $billingRequests : array_slice($billingRequests, 0, 10);
$billingChargeRows = $billingShowFullHistory ? $billingCharges : array_slice($billingCharges, 0, 15);
$billingPaymentRows = $billingShowFullHistory ? $billingPayments : array_slice($billingPayments, 0, 10);
$billingDiscountRows = $billingShowFullHistory ? $billingDiscounts : array_slice($billingDiscounts, 0, 10);
$billingFullHistoryUrl = '../billing/view.php?visit=' . (int)$visit['id'] . '&history=full';
$billingRecentUrl = '../billing/view.php?visit=' . (int)$visit['id'];
$billingDiscountsByCharge = [];
foreach ($billingDiscounts as $discount) {
    if ((string)($discount['status'] ?? '') !== 'Active') {
        continue;
    }
    $chargeId = (int)($discount['patient_charge_id'] ?? 0);
    if ($chargeId <= 0) {
        continue;
    }
    $billingDiscountsByCharge[$chargeId] = ($billingDiscountsByCharge[$chargeId] ?? 0.0) + (float)($discount['discount_amount'] ?? 0);
}
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
            <?php if (!empty($canApplyBillingDiscount) && $billingInvoice): ?>
                <a class="btn-secondary" href="../billing/discount_create.php?visit=<?= (int)$visit['id'] ?>">Apply Discount</a>
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
            <span class="summary-label">Charge Discounts</span>
            <span class="summary-value">&#8358;<?= e(number_format($billingChargeDiscountsTotal, 2)) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Subtotal</span>
            <span class="summary-value">&#8358;<?= e(number_format($billingDiscountedSubtotal, 2)) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Invoice Discount</span>
            <span class="summary-value">&#8358;<?= e(number_format($billingInvoiceDiscountsTotal, 2)) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Discount</span>
            <span class="summary-value">&#8358;<?= e(number_format($billingDiscountsTotal, 2)) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Invoice Total</span>
            <span class="summary-value">&#8358;<?= e(number_format($billingInvoiceTotal, 2)) ?></span>
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
                        <?php $canCancelThisRequest = isset($billingService, $currentUser) && $billingService->canCancelBillingRequestRow($request, $currentUser); ?>
                        <tr>
                            <td><?= e((string)($request['description'] ?? '-')) ?></td>
                            <td><?= e((string)($request['department_name'] ?? '-')) ?></td>
                            <td><?= e((string)($request['suggested_item_name'] ?? '-')) ?></td>
                            <td><?= e((string)($request['display_quantity'] ?? '1')) ?></td>
                            <td><?= e((string)($request['status'] ?? 'Pending')) ?></td>
                            <td><?= e((string)($request['requested_by_name'] ?? '-')) ?></td>
                            <td>
                                <?php if (!empty($canReviewBillingRequest)): ?>
                                    <a class="btn-secondary btn-sm" href="../billing/request_review.php?id=<?= (int)$request['id'] ?>">Review</a>
                                <?php endif; ?>
                                <?php if ($canCancelThisRequest): ?>
                                    <details class="inline-details">
                                        <summary class="btn-secondary btn-sm">Cancel</summary>
                                        <form method="post" action="../billing/request_cancel.php" class="inline-cancel-form">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="billing_request_id" value="<?= (int)$request['id'] ?>">
                                            <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
                                            <textarea name="reason" rows="2" required placeholder="Cancellation reason"></textarea>
                                            <button class="btn-danger btn-sm" type="submit">Confirm Cancel</button>
                                        </form>
                                    </details>
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
                <tr><th>Gross Charges</th><td>&#8358;<?= e(number_format($billingChargesTotal, 2)) ?></td></tr>
                <tr><th>Charge Discounts</th><td>&#8358;<?= e(number_format($billingChargeDiscountsTotal, 2)) ?></td></tr>
                <tr><th>Subtotal</th><td>&#8358;<?= e(number_format($billingDiscountedSubtotal, 2)) ?></td></tr>
                <tr><th>Invoice Discount</th><td>&#8358;<?= e(number_format($billingInvoiceDiscountsTotal, 2)) ?></td></tr>
                <tr><th>Total Discount</th><td>&#8358;<?= e(number_format($billingDiscountsTotal, 2)) ?></td></tr>
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

<?php if (!empty($canViewBillingDiscounts) || !empty($canApplyBillingDiscount)): ?>
<div class="card">
    <div class="section-header">
        <div>
            <h3>Discounts</h3>
            <p class="text-muted">Only approved preset discounts of 5%, 10%, or 15% are allowed.</p>
            <?php if (count($billingDiscounts) > count($billingDiscountRows)): ?>
                <p class="text-muted">Showing latest <?= count($billingDiscountRows) ?> of <?= count($billingDiscounts) ?> discounts.</p>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <?php if (!empty($canApplyBillingDiscount) && $billingInvoice): ?>
                <a class="btn-secondary" href="../billing/discount_create.php?visit=<?= (int)$visit['id'] ?>">Apply Discount</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$billingDiscountsReady): ?>
        <div class="empty-state">Billing discount tables are not available yet. Apply Migration 072 to enable discounts.</div>
    <?php elseif (empty($billingDiscounts)): ?>
        <div class="empty-state">No discounts have been applied.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Discount</th>
                        <th>Applies To</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Applied By</th>
                        <th>Applied At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($billingDiscountRows as $discount): ?>
                        <tr>
                            <td><?= e((string)($discount['display_discount_value'] ?? $discount['discount_value'] ?? '0')) ?>%</td>
                            <td>
                                <?= e((string)($discount['discount_scope_label'] ?? (!empty($discount['patient_charge_id']) ? 'Charge #' . (int)$discount['patient_charge_id'] : 'Whole Invoice'))) ?>
                            </td>
                            <td>&#8358;<?= e((string)($discount['display_discount_amount'] ?? '0.00')) ?></td>
                            <td><?= e((string)($discount['reason'] ?? '-')) ?></td>
                            <td><?= e((string)($discount['status'] ?? 'Active')) ?></td>
                            <td><?= e((string)($discount['applied_by_name'] ?? '-')) ?></td>
                            <td><?= e((string)($discount['applied_at'] ?? '-')) ?></td>
                            <td>
                                <?php if (!empty($canCancelBillingDiscount) && (string)($discount['status'] ?? '') === 'Active'): ?>
                                    <form method="post" action="../billing/discount_cancel.php" style="display:inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="discount_id" value="<?= (int)$discount['id'] ?>">
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
        <?php if (count($billingDiscounts) > count($billingDiscountRows)): ?>
            <div class="form-actions">
                <a class="btn-secondary" href="<?= e($billingFullHistoryUrl) ?>">View Full Discount History</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

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
                        <th>Line Discount</th>
                        <th>Net Amount</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($billingChargeRows as $charge): ?>
                        <?php
                            $chargeAmount = (float)($charge['amount'] ?? 0);
                            $chargeDiscount = (float)($billingDiscountsByCharge[(int)$charge['id']] ?? 0);
                            $chargeNetAmount = max(0, $chargeAmount - $chargeDiscount);
                        ?>
                        <tr>
                            <td><?= e((string)($charge['item_name'] ?? '-')) ?></td>
                            <td><?= e((string)($charge['display_quantity'] ?? '0')) ?></td>
                            <td>&#8358;<?= e((string)($charge['display_unit_price'] ?? '0.00')) ?></td>
                            <td>&#8358;<?= e((string)($charge['display_amount'] ?? '0.00')) ?></td>
                            <td>&#8358;<?= e(number_format($chargeDiscount, 2)) ?></td>
                            <td>&#8358;<?= e(number_format($chargeNetAmount, 2)) ?></td>
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
                        <th>Status</th>
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
                            <td><?= e((string)($payment['status'] ?? 'Active')) ?></td>
                            <td><?= e((string)($payment['received_by_name'] ?? '-')) ?></td>
                            <td>
                                <?php if (!empty($canViewReceipts)): ?>
                                        <a class="btn-secondary btn-sm" href="../billing/receipt.php?id=<?= (int)$payment['id'] ?>">Receipt</a>
                                <?php endif; ?>
                                <?php if (!empty($canCancelPayment) && (string)($payment['status'] ?? 'Active') === 'Active'): ?>
                                    <details class="inline-details">
                                        <summary class="btn-secondary btn-sm">Cancel Payment</summary>
                                        <form method="post" action="../billing/payment_cancel.php" class="inline-cancel-form">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="payment_id" value="<?= (int)$payment['id'] ?>">
                                            <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
                                            <textarea name="reason" rows="2" required placeholder="Cancellation reason"></textarea>
                                            <button class="btn-danger btn-sm" type="submit">Confirm Cancel</button>
                                        </form>
                                    </details>
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

