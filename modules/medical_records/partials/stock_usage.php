<?php

declare(strict_types=1);

$patientStockUsageHistory = $patientStockUsageHistory ?? [];
$latestPatientStockUsage = $latestPatientStockUsage ?? null;
$patientStockUsagePreviewRows = array_slice($patientStockUsageHistory, 0, 10);
?>

<section class="card">
    <div class="page-header compact">
        <div>
            <h2>Patient Stock Usage</h2>
            <p>Read-only history of department stock used for this patient.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="../patient_stock_usage/history.php?patient=<?= (int)$patient['id'] ?>">Open Full History</a>
        </div>
    </div>

    <?php if (!$patientStockUsageTablesReady): ?>
        <p class="text-muted">Patient Stock Usage tables are not available yet. Apply Migration 053 to enable this section.</p>
    <?php elseif ($patientStockUsageHistory === []): ?>
        <p class="text-muted">No patient stock usage recorded.</p>
    <?php else: ?>
        <?php if (count($patientStockUsageHistory) > count($patientStockUsagePreviewRows)): ?>
            <p class="text-muted">Showing latest <?= count($patientStockUsagePreviewRows) ?> of <?= count($patientStockUsageHistory) ?> patient stock usage records.</p>
        <?php endif; ?>
        <?php if ($latestPatientStockUsage !== null): ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Latest Date</span> <span class="summary-value"><?= e((string)$latestPatientStockUsage['created_at']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Department</span> <span class="summary-value"><?= e((string)($latestPatientStockUsage['department_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Item</span> <span class="summary-value"><?= e((string)($latestPatientStockUsage['item_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Quantity</span> <span class="summary-value"><?= e((string)$latestPatientStockUsage['quantity']) ?> <?= e((string)($latestPatientStockUsage['unit'] ?? '')) ?></span></div>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Encounter</th>
                        <th>Department</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Recorded By</th>
                        <th>Billing Request</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patientStockUsagePreviewRows as $usage): ?>
                        <tr>
                            <td><?= e((string)$usage['created_at']) ?></td>
                            <td><?= e((string)($usage['visit_number'] ?? ('#' . (int)$usage['visit_id']))) ?></td>
                            <td><?= e((string)($usage['department_name'] ?? '-')) ?></td>
                            <td><?= e((string)($usage['item_name'] ?? '-')) ?></td>
                            <td><?= e((string)$usage['quantity']) ?> <?= e((string)($usage['unit'] ?? '')) ?></td>
                            <td><?= e((string)($usage['recorded_by_name'] ?? '-')) ?></td>
                            <td>
                                <?= !empty($usage['billing_request_id'])
                                    ? '#' . (int)$usage['billing_request_id'] . ' ' . e((string)($usage['billing_request_status'] ?? ''))
                                    : 'Not requested' ?>
                            </td>
                            <td>
                                <a class="btn-secondary btn-sm" href="../patient_stock_usage/view.php?id=<?= (int)$usage['id'] ?>">View</a>
                                <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$usage['visit_id'] ?>&tab=stock_usage">Open Encounter</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
