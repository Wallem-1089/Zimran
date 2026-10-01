<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$permissionService->canViewBillingRequests($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to view registration billing requests.');
}

$filters = [
    'status' => trim((string)($_GET['status'] ?? 'Pending')),
    'billing_type' => trim((string)($_GET['billing_type'] ?? '')),
    'registration_type' => trim((string)($_GET['registration_type'] ?? '')),
    'patient_name' => trim((string)($_GET['patient_name'] ?? '')),
    'hospital_number' => trim((string)($_GET['hospital_number'] ?? '')),
    'payment_reference' => trim((string)($_GET['payment_reference'] ?? '')),
];
$requests = $registrationBillingService->listRequests($filters);
$canClearRegistrationPayments = $permissionService->canReviewBillingRequest($currentUser)
    || $permissionService->canRecordPayment($currentUser);

$pageTitle = 'Registration Billing Requests';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert-danger"><?= e((string)$_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1>Registration Billing Requests</h1>
            <p>Clear initial registration and monthly renewal payments before encounters continue.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">Billing Home</a>
        </div>
    </div>

    <form method="get" class="card">
        <div class="form-grid">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (['Pending', 'Paid', 'Cancelled', ''] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($status === '' ? 'All' : $status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="billing_type">Billing Type</label>
                <select id="billing_type" name="billing_type">
                    <option value="">All</option>
                    <?php foreach (['InitialRegistration' => 'Initial Registration', 'MonthlyRenewal' => 'Monthly Renewal'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $filters['billing_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="registration_type">Registration Type</label>
                <select id="registration_type" name="registration_type">
                    <option value="">All</option>
                    <?php foreach (['Normal', 'Emergency'] as $type): ?>
                        <option value="<?= e($type) ?>" <?= $filters['registration_type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
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
                <label for="payment_reference">Payment Reference / Notes</label>
                <input id="payment_reference" name="payment_reference" value="<?= e($filters['payment_reference']) ?>" placeholder="Receipt / transfer / POS reference">
            </div>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Filter</button>
            <a class="btn-secondary" href="registration_requests.php">Reset</a>
        </div>
    </form>

    <div class="card">
        <h3>Requests</h3>
        <?php if ($requests === []): ?>
            <div class="empty-state">No registration billing requests found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Hospital No.</th>
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
                        <?php foreach ($requests as $request): ?>
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
                                    <?php if ((string)$request['status'] === 'Pending' && $canClearRegistrationPayments): ?>
                                        <form method="post" action="registration_request_pay.php" class="inline-form">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                            <input name="notes" placeholder="Payment reference / notes">
                                            <button class="btn-primary btn-sm" type="submit">Mark Paid</button>
                                        </form>
                                    <?php elseif ((string)$request['status'] === 'Paid'): ?>
                                        <span class="status-badge status-success">Payment Cleared</span>
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
