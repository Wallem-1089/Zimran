<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$record = $latestPhysiotherapyRecord ?? null;
$session = $latestPhysiotherapySession ?? null;
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$requestSource = $physiotherapyRequestSource ?? 'Clinical';
?>

<section id="tab-physiotherapy" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Physiotherapy</h2>
                <p>Physiotherapy records and sessions linked to this encounter.</p>
            </div>
            <div>
                <?php if (!empty($canOpenPhysiotherapyWorklist)): ?>
                    <a class="btn-secondary" href="../physiotherapy/index.php">Worklist</a>
                <?php endif; ?>
                <?php if (!$physiotherapyTablesReady): ?>
                    <span class="badge badge-warning">Migration required</span>
                <?php elseif (!$canViewPhysiotherapy): ?>
                    <span class="badge badge-warning">No physiotherapy permission</span>
                <?php elseif ($record === null && !$isClosedEncounter && $canCreatePhysiotherapyRequest): ?>
                    <a href="../physiotherapy/request.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        <?= $requestSource === 'Direct' ? 'Start Direct Record' : 'Refer to Physiotherapy' ?>
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
                <span class="summary-label">Latest Record</span>
                <span class="summary-value"><?= e((string)($record['created_at'] ?? 'Not recorded')) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Status</span>
                <span class="summary-value"><?= e((string)($record['status'] ?? 'Not recorded')) ?></span>
            </div>
        </div>
    </div>

    <?php if (!$physiotherapyTablesReady): ?>
        <div class="card">
            <p>Physiotherapy tables are not available yet. Apply Migration 028 to enable this section.</p>
        </div>
    <?php elseif (!$canViewPhysiotherapy): ?>
        <div class="card alert-warning">
            You do not have permission to view physiotherapy records.
        </div>
    <?php elseif ($record === null): ?>
        <div class="card">
            <p class="text-muted">No Physiotherapy record.</p>
            <?php if (!$isClosedEncounter && $canCreatePhysiotherapyRequest): ?>
                <p>
                    <a href="../physiotherapy/request.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        <?= $requestSource === 'Direct' ? 'Start Direct Record' : 'Refer to Physiotherapy' ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Latest Record</h3>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Presenting Problem</span> <span class="summary-value"><?= e((string)($record['presenting_problem'] ?? '')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Source</span> <span class="summary-value"><?= e((string)$record['record_source']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$record['status']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Physiotherapist</span> <span class="summary-value"><?= e((string)($record['physiotherapist_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Sessions</span> <span class="summary-value"><?= e((string)($record['session_count'] ?? 0)) ?></span></div>
                <div class="summary-item"><span class="summary-label">Created</span> <span class="summary-value"><?= e((string)($record['created_at'] ?? '-')) ?></span></div>
            </div>

            <div class="form-actions">
                <a href="../physiotherapy/view.php?id=<?= (int)$record['id'] ?>" class="btn-secondary">View</a>
                <a href="../physiotherapy/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View History</a>
                <?php if (!$isClosedEncounter && $canEditPhysiotherapy && (string)$record['status'] === 'Active'): ?>
                    <a class="btn-secondary" href="../physiotherapy/edit.php?id=<?= (int)$record['id'] ?>">Edit Record</a>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && $canManagePhysiotherapySessions && (string)$record['status'] === 'Active'): ?>
                    <a class="btn-primary" href="../physiotherapy/report.php?record=<?= (int)$record['id'] ?>">
                        <?= $session ? 'Edit Latest Session' : 'Add Session' ?>
                    </a>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && $canCompletePhysiotherapyRequest && (string)$record['status'] === 'Active'): ?>
                    <form method="post" action="../physiotherapy/complete.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$record['id'] ?>">
                        <button type="submit" class="btn-secondary">Complete</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($session && trim((string)($session['treatment_given'] ?? '')) !== ''): ?>
            <div class="card">
                <h3>Latest Session</h3>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Date</span> <span class="summary-value"><?= e((string)($session['session_date'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Treatment Given</span> <span class="summary-value"><?= e((string)($session['treatment_given'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Patient Response</span> <span class="summary-value"><?= e((string)($session['patient_response'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Recorded By</span> <span class="summary-value"><?= e((string)($session['recorded_by_name'] ?? '-')) ?></span></div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <p class="text-muted">No physiotherapy session recorded.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
