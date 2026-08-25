<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$patientStockUsageTablesReady = $patientStockUsageTablesReady ?? false;
$patientStockUsageRecords = $patientStockUsageRecords ?? [];
$latestPatientStockUsage = $latestPatientStockUsage ?? ($patientStockUsageRecords[0] ?? null);
$canViewPatientStockUsage = $canViewPatientStockUsage ?? false;
$canRecordPatientStockUsage = $canRecordPatientStockUsage ?? false;
?>

<section id="tab-stock-usage" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Patient Stock Usage</h2>
                <p>Department stock used for this patient encounter. Stock decreases through Store inventory; billing is only requested for Accounts review.</p>
            </div>
            <div class="form-actions">
                <?php if ($patientStockUsageTablesReady && $canViewPatientStockUsage): ?>
                    <a class="btn-secondary" href="../patient_stock_usage/history.php?visit=<?= (int)$visit['id'] ?>">View History</a>
                <?php endif; ?>
                <?php if ($patientStockUsageTablesReady && $canRecordPatientStockUsage && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser))): ?>
                    <a class="btn-primary" href="../patient_stock_usage/create.php?visit=<?= (int)$visit['id'] ?>">Record Patient Stock Usage</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Encounter</span> <span class="summary-value"><?= e((string)($visit['visit_number'] ?? ('#' . (int)$visit['id']))) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)($patient['hospital_number'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Total Usage Records</span> <span class="summary-value"><?= count($patientStockUsageRecords) ?></span></div>
            <div class="summary-item"><span class="summary-label">Latest Usage</span> <span class="summary-value"><?= e((string)($latestPatientStockUsage['created_at'] ?? 'Not recorded')) ?></span></div>
        </div>
    </div>

    <?php if (!$patientStockUsageTablesReady): ?>
        <div class="card">
            <p>Patient Stock Usage tables are not available yet. Apply Migration 053 to enable this section.</p>
        </div>
    <?php elseif (!$canViewPatientStockUsage): ?>
        <div class="card alert-warning">
            You do not have permission to view patient stock usage.
        </div>
    <?php elseif ($latestPatientStockUsage === null): ?>
        <div class="card">
            <p class="text-muted">No patient stock usage recorded for this encounter.</p>
            <?php if ($canRecordPatientStockUsage && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser))): ?>
                <p><a class="btn-primary" href="../patient_stock_usage/create.php?visit=<?= (int)$visit['id'] ?>">Record Patient Stock Usage</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Latest Stock Used</h3>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)($latestPatientStockUsage['department_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Item</span> <span class="summary-value"><?= e((string)($latestPatientStockUsage['item_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Quantity</span> <span class="summary-value"><?= e((string)$latestPatientStockUsage['quantity']) ?> <?= e((string)($latestPatientStockUsage['unit'] ?? '')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Recorded By</span> <span class="summary-value"><?= e((string)($latestPatientStockUsage['recorded_by_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Billing Request</span> <span class="summary-value"><?= !empty($latestPatientStockUsage['billing_request_id']) ? '#' . (int)$latestPatientStockUsage['billing_request_id'] . ' ' . e((string)($latestPatientStockUsage['billing_request_status'] ?? '')) : 'Not requested' ?></span></div>
            </div>
            <?php if (trim((string)($latestPatientStockUsage['usage_reason'] ?? '')) !== ''): ?>
                <h4>Usage Reason</h4>
                <p><?= nl2br(e((string)$latestPatientStockUsage['usage_reason'])) ?></p>
            <?php endif; ?>
            <div class="form-actions">
                <a class="btn-secondary" href="../patient_stock_usage/view.php?id=<?= (int)$latestPatientStockUsage['id'] ?>">View Latest</a>
                <a class="btn-secondary" href="../patient_stock_usage/history.php?visit=<?= (int)$visit['id'] ?>">View History</a>
                <?php if ($canRecordPatientStockUsage && !$isClosedEncounter): ?>
                    <a class="btn-primary" href="../patient_stock_usage/create.php?visit=<?= (int)$visit['id'] ?>">Record More Stock Used</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
