<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$workspaceConsultation = $consultation ?? null;
$consultationStatus = $workspaceConsultation['status'] ?? 'Not Started';
?>

<section id="tab-consultation" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Consultation</h2>
                <p>Encounter consultation, clinical assessment, diagnosis summary and treatment plan.</p>
            </div>
            <div>
                <?php if (!$canViewConsultation): ?>
                    <span class="badge badge-warning">No consultation permission</span>
                <?php elseif (!$workspaceConsultation && $canCreateConsultation): ?>
                    <a href="../consultation/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Start Consultation</a>
                <?php elseif ($workspaceConsultation): ?>
                    <a href="../consultation/view.php?id=<?= (int)$workspaceConsultation['id'] ?>" class="btn-secondary">View</a>
                    <?php if ((string)$workspaceConsultation['status'] === 'Draft' && $canEditConsultation): ?>
                        <a href="../consultation/edit.php?id=<?= (int)$workspaceConsultation['id'] ?>" class="btn-primary">Continue/Edit</a>
                    <?php endif; ?>
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
                <span class="summary-label">Consultation Status</span>
                <span class="summary-value"><?= e((string)$consultationStatus) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Clinical Doctor</span>
                <span class="summary-value"><?= e((string)($workspaceConsultation['doctor_name'] ?? $visit['doctor_name'] ?? 'Not Assigned')) ?></span>
            </div>
        </div>
    </div>

    <?php if (!$canViewConsultation): ?>
        <div class="card alert-warning">
            You do not have permission to view consultation details.
        </div>
    <?php elseif (!$workspaceConsultation): ?>
        <div class="card">
            <h3>No Consultation Yet</h3>
            <?php if (!($consultationTablesReady ?? true)): ?>
                <p>Consultation tables are not available yet. Apply Migration 022 to enable this section.</p>
            <?php else: ?>
                <p>No consultation record has been opened for this encounter.</p>
            <?php endif; ?>
            <?php if (!$canCreateConsultation): ?>
                <p class="text-muted">You can view the workspace, but you cannot start a consultation.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php
        $sections = [
            'presenting_complaint' => 'Presenting Complaint',
            'history_of_presenting_complaint' => 'History of Presenting Complaint',
            'examination_findings' => 'Examination Findings',
            'assessment' => 'Assessment',
            'diagnosis' => 'Diagnosis',
            'treatment_plan' => 'Treatment Plan',
            'advice' => 'Advice',
            'follow_up' => 'Follow Up',
            'referral_notes' => 'Referral Notes'
        ];
        ?>
        <?php foreach ($sections as $field => $label): ?>
            <div class="card">
                <h3><?= e($label) ?></h3>
                <?php if (trim((string)($workspaceConsultation[$field] ?? '')) === ''): ?>
                    <div class="empty-state">Not recorded.</div>
                <?php else: ?>
                    <p><?= nl2br(e((string)$workspaceConsultation[$field])) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if ((string)$workspaceConsultation['status'] === 'Draft' && $canCompleteConsultation): ?>
            <div class="card">
                <h3>Complete Consultation</h3>
                <p>Completing the consultation makes it view-only.</p>
                <form method="post" action="../consultation/complete.php">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$workspaceConsultation['id'] ?>">
                    <button type="submit" class="btn-primary">Complete Consultation</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
