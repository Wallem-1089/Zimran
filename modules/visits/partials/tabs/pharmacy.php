<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$latest = $latestPharmacyPrescription ?? null;
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$requestSource = $pharmacyRequestSource ?? 'Clinical';
?>

<section id="tab-pharmacy" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Pharmacy</h2>
                <p>Prescriptions, medication dispensing and pharmacy records linked to this encounter.</p>
            </div>
            <div>
                <a class="btn-secondary" href="../pharmacy/index.php">Worklist</a>
                <?php if (!$pharmacyTablesReady): ?>
                    <span class="badge badge-warning">Migration required</span>
                <?php elseif (!$canViewPharmacy): ?>
                    <span class="badge badge-warning">No pharmacy permission</span>
                <?php elseif (!$isClosedEncounter && $canCreatePrescription): ?>
                    <a href="../pharmacy/prescribe.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        <?= $requestSource === 'Direct' ? 'Create Direct Prescription' : 'Create Prescription' ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Encounter</span>
                <span class="summary-value"><?= e((string)($visit['visit_number'] ?? ('#' . (int)$visit['id']))) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Hospital Number</span>
                <span class="summary-value"><?= e((string)$patient['hospital_number']) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Latest Prescription</span>
                <span class="summary-value"><?= e((string)($latest['created_at'] ?? 'Not recorded')) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Status</span>
                <span class="summary-value"><?= e((string)($latest['status'] ?? 'Not recorded')) ?></span>
            </div>
        </div>
    </div>

    <?php if (!$pharmacyTablesReady): ?>
        <div class="card">
            <p>Pharmacy tables are not available yet. Apply Migration 032 to enable this section.</p>
        </div>
    <?php elseif (!$canViewPharmacy): ?>
        <div class="card alert-warning">
            You do not have permission to view pharmacy prescriptions.
        </div>
    <?php elseif ($latest === null): ?>
        <div class="card">
            <p class="text-muted">No prescriptions recorded.</p>
            <?php if (!$isClosedEncounter && $canCreatePrescription): ?>
                <p>
                    <a href="../pharmacy/prescribe.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        <?= $requestSource === 'Direct' ? 'Create Direct Prescription' : 'Create Prescription' ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Latest Prescription</h3>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Medication</span><span class="summary-value"><?= e((string)$latest['medication_name']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Source</span><span class="summary-value"><?= e((string)$latest['prescription_source']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Quantity</span><span class="summary-value"><?= e((string)$latest['quantity']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Status</span><span class="summary-value"><?= e((string)$latest['status']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Stock Available</span><span class="summary-value"><?= number_format((float)($latest['pharmacy_stock_available'] ?? 0), 2) ?></span></div>
                <div class="summary-item"><span class="summary-label">Requested By</span><span class="summary-value"><?= e((string)($latest['created_by_name'] ?? 'Unknown')) ?></span></div>
            </div>
            <div class="form-actions">
                <a href="../pharmacy/view.php?id=<?= (int)$latest['id'] ?>" class="btn-secondary">View</a>
                <a href="../pharmacy/index.php" class="btn-secondary">View Worklist</a>
                <?php if (!$isClosedEncounter && $canCreatePrescription): ?>
                    <a href="../pharmacy/prescribe.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-secondary">
                        <?= $requestSource === 'Direct' ? 'Create Direct Prescription' : 'Create Prescription' ?>
                    </a>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && $canDispensePrescription && (string)$latest['status'] === 'Prescribed'): ?>
                    <a href="../pharmacy/dispense.php?id=<?= (int)$latest['id'] ?>" class="btn-primary">Dispense</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ((string)($latest['status'] ?? '') === 'Dispensed' || $latest['dispensing_id'] !== null): ?>
            <div class="card">
                <h3>Latest Dispensing</h3>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Dispensed By</span><span class="summary-value"><?= e((string)($latest['dispensed_by_name'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Dispensed At</span><span class="summary-value"><?= e((string)($latest['dispensed_recorded_at'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Quantity Dispensed</span><span class="summary-value"><?= e((string)($latest['quantity_dispensed'] ?? '-')) ?></span></div>
                </div>
                <?php if (trim((string)($latest['dispensing_notes'] ?? '')) !== ''): ?>
                    <h4>Notes</h4>
                    <p><?= nl2br(e((string)$latest['dispensing_notes'])) ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <p class="text-muted">No dispensing record recorded.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
