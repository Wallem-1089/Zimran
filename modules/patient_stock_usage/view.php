<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$patientStockUsageTablesReady) {
    http_response_code(503);
    exit('Patient Stock Usage tables are not available yet. Apply Migration 053 to enable this section.');
}

$usageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$usage = $patientStockUsageService->getById($usageId, $currentUser);
if (!$usage) {
    http_response_code(404);
    exit('Patient stock usage record not found.');
}

$pageTitle = 'Patient Stock Usage';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Patient Stock Usage #<?= (int)$usage['id'] ?></h1>
            <p><?= e((string)($usage['visit_number'] ?? ('Encounter #' . (int)$usage['visit_id']))) ?></p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print</button>
            <a class="btn-secondary" href="history.php?visit=<?= (int)$usage['visit_id'] ?>">History</a>
            <a class="btn-secondary" href="<?= e(patientStockUsageBackToWorkspace((int)$usage['visit_id'])) ?>">Workspace</a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Patient</span> <span class="summary-value"><?= e((string)($usage['patient_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)($usage['hospital_number'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)($usage['department_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Item</span> <span class="summary-value"><?= e((string)($usage['item_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Quantity</span> <span class="summary-value"><?= e((string)$usage['quantity']) ?> <?= e((string)($usage['unit'] ?? '')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Recorded By</span> <span class="summary-value"><?= e((string)($usage['recorded_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Recorded At</span> <span class="summary-value"><?= e((string)($usage['created_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Billing Request</span> <span class="summary-value"><?= !empty($usage['billing_request_id']) ? '#' . (int)$usage['billing_request_id'] . ' ' . e((string)($usage['billing_request_status'] ?? '')) : 'Not requested' ?></span></div>
            <div class="summary-item"><span class="summary-label">Stock Transaction</span> <span class="summary-value"><?= !empty($usage['stock_transaction_id']) ? '#' . (int)$usage['stock_transaction_id'] : '-' ?></span></div>
        </div>

        <?php if (trim((string)($usage['usage_reason'] ?? '')) !== ''): ?>
            <h3>Usage Reason</h3>
            <p><?= nl2br(e((string)$usage['usage_reason'])) ?></p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
