<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

if (!isset($billingTablesReady, $billingSummary, $billingCharges, $billingPayments)) {
    return;
}

?>

<section id="tab-billing" class="workspace-tab">
    <?php if (isset($canViewBilling) && !$canViewBilling): ?>
        <div class="card alert-warning">
            You do not have permission to view billing.
        </div>
    <?php elseif (!$billingTablesReady): ?>
        <div class="card">
            <h2>Billing</h2>
            <div class="empty-state">Billing tables are not available yet. Apply Migration 033 to enable this section.</div>
        </div>
    <?php else: ?>
        <?php require __DIR__ . '/../../../billing/_summary.php'; ?>
    <?php endif; ?>
</section>
