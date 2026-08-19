<?php

declare(strict_types=1);

if (!isset($visit)) {
    return;
}

$admissionTablesReady = $admissionTablesReady ?? false;
$admission = $admission ?? null;
$canViewAdmissions = $canViewAdmissions ?? false;
$canCreateAdmission = $canCreateAdmission ?? false;
$canTransferAdmission = $canTransferAdmission ?? false;
$canDischargeAdmission = $canDischargeAdmission ?? false;
$encounterLocked = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
?>

<section class="card">
    <div class="section-header">
        <div>
            <h2>Admission</h2>
            <p>Inpatient ward and bed status for this encounter.</p>
        </div>
        <div class="form-actions">
            <?php if ($admissionTablesReady && $canViewAdmissions): ?>
                <a class="btn-secondary" href="../admissions/index.php">Admission Census</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$admissionTablesReady): ?>
        <div class="empty-state">Admission tables are not available yet. Apply Migration 037 to enable this section.</div>
    <?php elseif (!$canViewAdmissions): ?>
        <div class="empty-state">You do not have permission to view admissions.</div>
    <?php elseif ($admission === null): ?>
        <div class="empty-state">No inpatient admission recorded for this encounter.</div>
        <?php if ($canCreateAdmission && !$encounterLocked): ?>
            <div class="form-actions">
                <a class="btn-primary" href="../admissions/create.php?visit=<?= (int)$visit['id'] ?>">Admit Patient</a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Ward</span> <span class="summary-value"><?= e((string)$admission['ward_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Bed</span> <span class="summary-value"><?= e((string)$admission['bed_label']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Type</span> <span class="summary-value"><?= e((string)$admission['admission_type']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$admission['status']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Admitted By</span> <span class="summary-value"><?= e((string)($admission['admitted_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Admitted At</span> <span class="summary-value"><?= e((string)$admission['admitted_at']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Discharged At</span> <span class="summary-value"><?= e((string)($admission['discharged_at'] ?? 'Not discharged')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Diagnosis</span> <span class="summary-value"><?= e((string)($admission['admission_diagnosis'] ?? '-')) ?></span></div>
        </div>

        <div class="form-actions">
            <a class="btn-secondary" href="../admissions/view.php?id=<?= (int)$admission['id'] ?>">View Admission</a>
            <?php if (!$encounterLocked && in_array((string)$admission['status'], ['Admitted','Transferred'], true)): ?>
                <?php if ($canTransferAdmission): ?>
                    <a class="btn-secondary" href="../admissions/transfer.php?id=<?= (int)$admission['id'] ?>">Transfer Bed/Ward</a>
                <?php endif; ?>
                <?php if ($canDischargeAdmission): ?>
                    <a class="btn-primary" href="../admissions/discharge.php?id=<?= (int)$admission['id'] ?>">Discharge</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
