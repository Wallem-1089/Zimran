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
    'encounter_id' => trim((string)($_GET['encounter_id'] ?? '')),
    'payment_reference' => trim((string)($_GET['payment_reference'] ?? '')),
    'patient_name' => trim((string)($_GET['patient_name'] ?? '')),
    'hospital_number' => trim((string)($_GET['hospital_number'] ?? '')),
    'visit_number' => trim((string)($_GET['visit_number'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
];
$showFullHistory = (string)($_GET['full'] ?? '') === '1';
$hasSearchFilters = $filters['patient_name'] !== ''
    || $filters['hospital_number'] !== ''
    || $filters['visit_number'] !== ''
    || $filters['encounter_id'] !== ''
    || $filters['payment_reference'] !== ''
    || $filters['invoice_number'] !== ''
    || $filters['status'] !== '';

$invoices = $billingTablesReady ? $billingService->listInvoices($filters, $currentUser) : [];
$encounterMatches = ($filters['patient_name'] !== '' || $filters['hospital_number'] !== '' || $filters['visit_number'] !== '' || $filters['encounter_id'] !== '' || $filters['payment_reference'] !== '')
    ? $billingService->searchEncountersForBilling($filters, $currentUser)
    : [];
$recentPayments = $billingTablesReady ? $billingService->listPaymentsFiltered($filters, $currentUser) : [];
$registrationPayments = $registrationBillingService->listRegistrationPaymentsFiltered($filters, $currentUser);
$registrationBillingMatches = ($filters['patient_name'] !== '' || $filters['hospital_number'] !== '' || $filters['payment_reference'] !== '' || $filters['status'] !== '')
    ? $registrationBillingService->listRequests([
        'status' => in_array($filters['status'], ['Pending', 'Paid', 'Cancelled'], true) ? $filters['status'] : '',
        'patient_name' => $filters['patient_name'],
        'hospital_number' => $filters['hospital_number'],
        'payment_reference' => $filters['payment_reference'],
    ])
    : [];
$displayInvoices = $showFullHistory ? $invoices : array_slice($invoices, 0, 50);
$displayPayments = $showFullHistory ? $recentPayments : array_slice($recentPayments, 0, 20);
$displayRegistrationPayments = $showFullHistory ? $registrationPayments : array_slice($registrationPayments, 0, 20);
$billingRequestCount = ($billingTablesReady && $billingRequestsReady && $permissionService->canViewBillingRequests($currentUser))
    ? count($billingService->listBillingRequests(['status' => 'Pending'], $currentUser))
    : 0;
$registrationBillingRequestCount = $permissionService->canViewBillingRequests($currentUser)
    ? count($registrationBillingService->listRequests(['status' => 'Pending']))
    : 0;
$openInvoices = count(array_filter($invoices, static fn (array $invoice): bool => in_array((string)($invoice['status'] ?? ''), ['Unpaid', 'Partially Paid'], true)));
$paymentCount = count($recentPayments) + count($registrationPayments);
$activeDepartmentName = (string)($currentUser['active_department_name'] ?? $currentUser['department_name'] ?? '');
$canShowManualChargeButton = $permissionService->canCreatePatientCharge($currentUser)
    && strcasecmp($activeDepartmentName, 'Accounts') !== 0;

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
            <p>Patient Accounts, invoices, encounter payments, and registration payment clearances.</p>
        </div>
        <div class="form-actions">
            <?php if ($permissionService->canViewBillingRequests($currentUser)): ?>
                <a class="btn-primary" href="billing_requests.php">Billing Requests</a>
                <a class="btn-primary" href="registration_requests.php">Registration Requests</a>
            <?php endif; ?>
            <?php if (!$showFullHistory): ?>
                <a class="btn-secondary" href="index.php?<?= e(http_build_query(array_merge($_GET, ['full' => '1']))) ?>">Full History</a>
            <?php else: ?>
                <a class="btn-secondary" href="index.php?<?= e(http_build_query(array_diff_key($_GET, ['full' => true]))) ?>">Show Recent</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item"><span class="summary-label">Open Invoices</span> <span class="summary-value"><?= $openInvoices ?></span></div>
        <div class="summary-item"><span class="summary-label">Payments / Clearances</span> <span class="summary-value"><?= $paymentCount ?></span></div>
        <div class="summary-item"><span class="summary-label">Filtered Invoices</span> <span class="summary-value"><?= count($invoices) ?></span></div>
        <?php if ($permissionService->canViewBillingRequests($currentUser)): ?>
            <div class="summary-item"><span class="summary-label">Pending Requests</span> <span class="summary-value"><?= $billingRequestCount ?></span></div>
            <div class="summary-item"><span class="summary-label">Pending Registration</span> <span class="summary-value"><?= $registrationBillingRequestCount ?></span></div>
        <?php endif; ?>
    </div>

    <form method="get" class="card">
        <div class="form-grid">
            <div class="form-group">
                <label for="invoice_number">Invoice Number</label>
                <input id="invoice_number" name="invoice_number" value="<?= e($filters['invoice_number']) ?>">
            </div>
            <div class="form-group">
                <label for="encounter_id">Encounter ID</label>
                <input id="encounter_id" name="encounter_id" inputmode="numeric" value="<?= e($filters['encounter_id']) ?>" placeholder="e.g. 32">
            </div>
            <div class="form-group">
                <label for="payment_reference">Transaction Reference</label>
                <input id="payment_reference" name="payment_reference" value="<?= e($filters['payment_reference']) ?>" placeholder="Receipt / transfer / POS reference">
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
                <label for="status">Invoice / Request Status</label>
                <select id="status" name="status">
                    <option value="">All</option>
                    <?php foreach (['Unpaid', 'Partially Paid', 'Paid', 'Pending', 'Cancelled'] as $status): ?>
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

    <?php if ($filters['patient_name'] !== '' || $filters['hospital_number'] !== '' || $filters['visit_number'] !== '' || $filters['encounter_id'] !== '' || $filters['payment_reference'] !== ''): ?>
        <div class="card">
            <h3>Select Patient Encounter</h3>
            <p class="text-muted">Choose the encounter you want to bill. Patient name, hospital number, and visit number will be carried into the billing page.</p>

            <?php if ($encounterMatches === []): ?>
                <div class="empty-state">No matching patient encounters found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Hospital Number</th>
                            <th>Visit Number</th>
                            <th>Encounter ID</th>
                            <th>Visit Status</th>
                                <th>Department</th>
                                <th>Billing Status</th>
                                <th>Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($encounterMatches as $match): ?>
                                <tr>
                                    <td><?= e((string)($match['patient_name'] ?? '-')) ?></td>
                                    <td><?= e((string)($match['hospital_number'] ?? '-')) ?></td>
                                    <td><?= e((string)($match['visit_number'] ?? ('#' . (int)$match['visit_id']))) ?></td>
                                    <td>#<?= (int)$match['visit_id'] ?></td>
                                    <td><?= e((string)($match['visit_status'] ?? '-')) ?></td>
                                    <td><?= e((string)($match['department_name'] ?? '-')) ?></td>
                                    <td><?= e((string)($match['billing_status'] ?? 'Unbilled')) ?></td>
                                    <td>₦<?= e(number_format((float)($match['balance_due'] ?? 0), 2)) ?></td>
                                    <td>
                                        <a class="btn-secondary btn-sm" href="view.php?visit=<?= (int)$match['visit_id'] ?>">Open Billing</a>
                                        <?php if ($canShowManualChargeButton && !in_array((string)($match['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)): ?>
                                            <a class="btn-primary btn-sm" href="charge_create.php?visit=<?= (int)$match['visit_id'] ?>">Add Charge</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($hasSearchFilters && $permissionService->canViewBillingRequests($currentUser)): ?>
        <div class="card">
            <h3>Registration Billing Matches</h3>
            <p class="text-muted">Initial registration and monthly renewal requests are patient-level billing records, so they may not have an encounter or invoice number.</p>

            <?php if ($registrationBillingMatches === []): ?>
                <div class="empty-state">No matching registration billing requests found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Hospital Number</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Cleared</th>
                                <th>Reference / Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrationBillingMatches as $request): ?>
                                <tr>
                                    <td><?= e((string)($request['patient_name'] ?? '-')) ?><br><small><?= e((string)($request['phone'] ?? '-')) ?></small></td>
                                    <td><?= e((string)($request['hospital_number'] ?? 'Pending')) ?></td>
                                    <td><?= e((string)$request['billing_type']) ?><?= !empty($request['registration_type']) ? ' / ' . e((string)$request['registration_type']) : '' ?></td>
                                    <td>&#8358;<?= e((string)$request['display_amount']) ?></td>
                                    <td><?= e((string)$request['status']) ?></td>
                                    <td><?= e((string)$request['requested_at']) ?><br><small><?= e((string)($request['requested_by_name'] ?? '-')) ?></small></td>
                                    <td><?= e((string)($request['cleared_at'] ?? '-')) ?><br><small><?= e((string)($request['cleared_by_name'] ?? '')) ?></small></td>
                                    <td><?= e((string)($request['notes'] ?? '-')) ?></td>
                                    <td>
                                        <a class="btn-secondary btn-sm" href="../patients/view.php?id=<?= (int)$request['patient_id'] ?>">Patient</a>
                                        <a class="btn-secondary btn-sm" href="registration_requests.php?<?= e(http_build_query([
                                            'status' => (string)$request['status'],
                                            'patient_name' => (string)($request['patient_name'] ?? ''),
                                            'hospital_number' => (string)($request['hospital_number'] ?? ''),
                                        ])) ?>">Registration Requests</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="section-header">
            <div>
                <h3>Open Invoices</h3>
                <p class="text-muted"><?= $showFullHistory ? 'Showing full matching invoice history.' : 'Showing latest 50 matching invoices.' ?></p>
            </div>
        </div>
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
                            <th>Encounter ID</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayInvoices as $invoice): ?>
                            <tr>
                                <td><?= e((string)$invoice['invoice_number']) ?></td>
                                <td><?= e((string)($invoice['patient_name'] ?? '-')) ?></td>
                                <td><?= e((string)($invoice['visit_number'] ?? ('#' . (int)$invoice['visit_id']))) ?></td>
                                <td>#<?= (int)$invoice['visit_id'] ?></td>
                                <td>₦<?= e((string)$invoice['display_total_amount']) ?></td>
                                <td>₦<?= e((string)$invoice['display_amount_paid']) ?></td>
                                <td>₦<?= e((string)$invoice['display_balance_due']) ?></td>
                                <td><?= e((string)$invoice['status']) ?></td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="view.php?visit=<?= (int)$invoice['visit_id'] ?>">Open</a>
                                    <?php if ($permissionService->canViewReceipts($currentUser) && (float)$invoice['amount_paid'] > 0): ?>
                                        <a class="btn-secondary btn-sm" href="view.php?visit=<?= (int)$invoice['visit_id'] ?>#payments">Receipts</a>
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
        <div class="section-header">
            <div>
                <h3>Recent Payments</h3>
                <p class="text-muted"><?= $showFullHistory ? 'Showing full payment and registration clearance history.' : 'Showing latest 20 encounter payments and latest 20 registration clearances.' ?></p>
            </div>
        </div>
        <?php if ($recentPayments === [] && $registrationPayments === []): ?>
            <div class="empty-state">No payments recorded yet.</div>
        <?php else: ?>
            <?php if ($displayPayments !== []): ?>
                <h4>Encounter Invoice Payments</h4>
                <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Receipt</th>
                            <th>Patient</th>
                            <th>Visit</th>
                            <th>Encounter ID</th>
                            <th>Invoice</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayPayments as $payment): ?>
                            <tr>
                                <td>#<?= (int)$payment['id'] ?></td>
                                <td><?= e((string)($payment['patient_name'] ?? '-')) ?></td>
                                <td><?= e((string)($payment['visit_number'] ?? ('#' . (int)$payment['visit_id']))) ?></td>
                                <td>#<?= (int)$payment['visit_id'] ?></td>
                                <td><?= e((string)$payment['invoice_number']) ?></td>
                                <td><?= e((string)($payment['reference'] ?? '-')) ?></td>
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

            <?php if ($displayRegistrationPayments !== []): ?>
                <h4>Registration Payment Clearances</h4>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Request</th>
                                <th>Patient</th>
                                <th>Hospital Number</th>
                                <th>Type</th>
                                <th>Reference / Notes</th>
                                <th>Amount</th>
                                <th>Cleared By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($displayRegistrationPayments as $payment): ?>
                                <tr>
                                    <td>#<?= (int)$payment['id'] ?></td>
                                    <td><?= e((string)($payment['patient_name'] ?? '-')) ?></td>
                                    <td><?= e((string)($payment['hospital_number'] ?? '-')) ?></td>
                                    <td><?= e((string)$payment['billing_type']) ?><?= !empty($payment['registration_type']) ? ' / ' . e((string)$payment['registration_type']) : '' ?></td>
                                    <td><?= e((string)($payment['notes'] ?? '-')) ?></td>
                                    <td>&#8358;<?= e((string)$payment['display_amount']) ?></td>
                                    <td><?= e((string)($payment['cleared_by_name'] ?? '-')) ?></td>
                                    <td><?= e((string)($payment['cleared_at'] ?? '-')) ?></td>
                                    <td>
                                        <a class="btn-secondary btn-sm" href="../patients/view.php?id=<?= (int)$payment['patient_id'] ?>">Patient</a>
                                        <a class="btn-secondary btn-sm" href="registration_requests.php?status=Paid&patient_name=<?= e(urlencode((string)($payment['patient_name'] ?? ''))) ?>">Request</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
