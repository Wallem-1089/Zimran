<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$assessment = $nursing ?? null;
$latestVitals = $latestVitalSigns ?? null;
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
?>

<section id="tab-nursing" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Nursing Assessment</h2>
                <p>Nursing observations, interventions and handover notes.</p>
            </div>
            <div>
                <?php if (!$nursingTablesReady): ?>
                    <span class="badge badge-warning">Migration required</span>
                <?php elseif (!$canViewNursing): ?>
                    <span class="badge badge-warning">No nursing permission</span>
                <?php elseif ($assessment === null && $canCreateNursing && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser))): ?>
                    <a href="../nursing/assessment.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Start Nursing Assessment</a>
                <?php elseif ($assessment !== null): ?>
                    <a href="../nursing/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View History</a>
                    <?php if ($canCreateNursing && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser)) && (string)($assessment['status'] ?? '') === 'Draft'): ?>
                        <a href="../nursing/edit.php?id=<?= (int)$assessment['id'] ?>" class="btn-primary">Continue/Edit</a>
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
                <span class="summary-label">Latest Record</span>
                <span class="summary-value"><?= e($assessment['created_at'] ?? 'Not recorded') ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Nurse</span>
                <span class="summary-value"><?= e($assessment['nurse_name'] ?? 'Unknown') ?></span>
            </div>
        </div>
    </div>

    <?php if (!$nursingTablesReady): ?>
        <div class="card">
            <p>Nursing tables are not available yet. Apply Migration 024 to enable this section.</p>
        </div>
    <?php elseif (!$canViewNursing): ?>
        <div class="card alert-warning">
            You do not have permission to view nursing assessments.
        </div>
    <?php elseif ($assessment === null): ?>
        <div class="card">
            <p class="text-muted">No nursing assessment recorded.</p>
            <?php if ($canCreateNursing && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser))): ?>
                <p><a href="../nursing/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Start Nursing Assessment</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Latest Nursing Assessment</h3>
            <?php $latest = $assessment; require __DIR__ . '/../../../nursing/partials/record_card.php'; ?>
        </div>

        <div class="card">
            <div class="form-actions">
                <a href="../nursing/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View History</a>
                <a href="../medical_records/chart.php?patient=<?= (int)$patient['id'] ?>&tab=nursing&visit=<?= (int)$visit['id'] ?>" class="btn-secondary">Open Patient Chart</a>
                <?php if ($canCreateNursing && (!$isClosedEncounter || $permissionService->isAdministrator($currentUser))): ?>
                    <a href="../nursing/create.php?visit=<?= (int)$visit['id'] ?>" class="btn-primary">Start New Draft</a>
                <?php endif; ?>
                <?php if ($canEditNursing && !$isClosedEncounter && (string)($assessment['status'] ?? '') === 'Draft'): ?>
                    <a href="../nursing/edit.php?id=<?= (int)$assessment['id'] ?>" class="btn-secondary">Edit Draft</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($latestVitals !== null): ?>
        <div class="card">
            <h3>Latest Vital Signs</h3>
            <?php $latest = $latestVitals; require __DIR__ . '/../../../vital_signs/partials/record_card.php'; ?>
        </div>
    <?php endif; ?>
</section>
