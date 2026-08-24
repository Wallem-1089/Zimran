<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

billingRequireRequestAccess($permissionService, $currentUser);

if (!$billingTablesReady || !$billingRequestsReady) {
    http_response_code(503);
    exit('Billing request tables are not available yet. Apply Migration 044 to enable this section.');
}

$filters = [
    'status' => trim((string)($_GET['status'] ?? 'Pending')),
    'patient_name' => trim((string)($_GET['patient_name'] ?? '')),
    'hospital_number' => trim((string)($_GET['hospital_number'] ?? '')),
    'visit_number' => trim((string)($_GET['visit_number'] ?? '')),
];

$requests = $billingService->listBillingRequests($filters, $currentUser);
$pendingCount = count(array_filter($requests, static fn (array $row): bool => (string)($row['status'] ?? '') === 'Pending'));

$pageTitle = 'Billing Requests';
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
            <h1>Billing Requests</h1>
            <p>Department recommendations waiting for Accounts to convert into official patient charges.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">Billing Home</a>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item"><span class="summary-label">Filtered Requests</span> <span class="summary-value"><?= count($requests) ?></span></div>
        <div class="summary-item"><span class="summary-label">Pending</span> <span class="summary-value"><?= $pendingCount ?></span></div>
    </div>

    <form method="get" class="card">
        <div class="form-grid">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (['Pending', 'Charged', 'Cancelled', ''] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($status === '' ? 'All' : $status) ?></option>
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
                <label for="visit_number">Visit Number</label>
                <input id="visit_number" name="visit_number" value="<?= e($filters['visit_number']) ?>">
            </div>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Filter</button>
            <a class="btn-secondary" href="billing_requests.php">Reset</a>
        </div>
    </form>

    <div class="card">
        <h3>Requests</h3>
        <?php if ($requests === []): ?>
            <div class="empty-state">No billing requests found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Visit</th>
                            <th>Department</th>
                            <th>Description</th>
                            <th>Suggested Item</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= e((string)($request['patient_name'] ?? '-')) ?><br><small><?= e((string)($request['hospital_number'] ?? '-')) ?></small></td>
                                <td><?= e((string)($request['visit_number'] ?? ('#' . (int)$request['visit_id']))) ?></td>
                                <td><?= e((string)($request['department_name'] ?? '-')) ?></td>
                                <td><?= e((string)($request['description'] ?? '-')) ?></td>
                                <td><?= e((string)($request['suggested_item_name'] ?? '—')) ?></td>
                                <td><?= e((string)($request['display_quantity'] ?? '1')) ?></td>
                                <td><?= e((string)($request['status'] ?? 'Pending')) ?></td>
                                <td><?= e((string)($request['requested_by_name'] ?? '-')) ?></td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="view.php?visit=<?= (int)$request['visit_id'] ?>">Open Billing</a>
                                    <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$request['visit_id'] ?>&tab=billing">Workspace</a>
                                    <?php if ($permissionService->canReviewBillingRequest($currentUser) && (string)($request['status'] ?? '') === 'Pending'): ?>
                                        <a class="btn-primary btn-sm" href="request_review.php?id=<?= (int)$request['id'] ?>">Create Charge</a>
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
