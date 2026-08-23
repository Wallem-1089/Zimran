<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$latest = $latestLaboratoryRequest ?? null;
$result = $latestLaboratoryResult ?? null;
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$requestSource = $laboratoryRequestSource ?? 'Clinical';
?>

<section id="tab-laboratory" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Laboratory</h2>
                <p>Laboratory requests and results linked to this encounter.</p>
            </div>
            <div>
                <?php if (!empty($canOpenLaboratoryWorklist)): ?>
                    <a class="btn-secondary" href="../laboratory/index.php">Worklist</a>
                <?php endif; ?>
                <?php if (!$laboratoryTablesReady): ?>
                    <span class="badge badge-warning">Migration required</span>
                <?php elseif (!$canViewLaboratory): ?>
                    <span class="badge badge-warning">No laboratory permission</span>
                <?php elseif (!$isClosedEncounter && $canCreateLaboratoryRequest): ?>
                    <a href="../laboratory/create.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        <?= $requestSource === 'Direct' ? 'Create Direct Request' : 'Request Laboratory Test' ?>
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
                <span class="summary-label">Latest Request</span>
                <span class="summary-value"><?= e((string)($latest['created_at'] ?? 'Not recorded')) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Status</span>
                <span class="summary-value"><?= e((string)($latest['status'] ?? 'Not recorded')) ?></span>
            </div>
        </div>
    </div>

    <?php if (!$laboratoryTablesReady): ?>
        <div class="card">
            <p>Laboratory tables are not available yet. Apply Migration 025 to enable this section.</p>
        </div>
    <?php elseif (!$canViewLaboratory): ?>
        <div class="card alert-warning">
            You do not have permission to view laboratory requests.
        </div>
    <?php elseif ($latest === null): ?>
        <div class="card">
            <p class="text-muted">No Laboratory requests.</p>
            <?php if (!$isClosedEncounter && $canCreateLaboratoryRequest): ?>
                <p>
                    <a href="../laboratory/create.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        Request Laboratory Test
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Latest Request</h3>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Tests Requested</span> <span class="summary-value"><?= e((string)$latest['tests_requested']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Request Source</span> <span class="summary-value"><?= e((string)$latest['request_source']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Priority</span> <span class="summary-value"><?= e((string)$latest['priority']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Result Status</span> <span class="summary-value"><?= e((string)($latest['result_status'] ?? 'Pending')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Requested By</span> <span class="summary-value"><?= e((string)($latest['requested_by_name'] ?? 'Unknown')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Requested</span> <span class="summary-value"><?= e((string)($latest['created_at'] ?? '-')) ?></span></div>
            </div>
            <div class="form-actions">
                <a href="../laboratory/view.php?id=<?= (int)$latest['id'] ?>" class="btn-secondary">View</a>
                <a href="../laboratory/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View History</a>
                <?php if (!$isClosedEncounter && $canProcessLaboratoryRequest && (string)$latest['status'] === 'Requested'): ?>
                    <form method="post" action="../laboratory/start.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$latest['id'] ?>">
                        <button type="submit" class="btn-primary">Start</button>
                    </form>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && ($canEnterLaboratoryResult || $canEditLaboratoryResult)): ?>
                    <a href="../laboratory/result.php?id=<?= (int)$latest['id'] ?>" class="btn-secondary">
                        <?= $result ? 'Edit Result' : 'Enter Result' ?>
                    </a>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && $canCompleteLaboratoryRequest): ?>
                    <form method="post" action="../laboratory/complete.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$latest['id'] ?>">
                        <button type="submit" class="btn-secondary">Complete</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($result && trim((string)($result['result'] ?? '')) !== ''): ?>
            <div class="card">
                <h3>Latest Result</h3>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Performed By</span> <span class="summary-value"><?= e((string)($result['performed_by_name'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Completed At</span> <span class="summary-value"><?= e((string)($result['result_completed_at'] ?? '-')) ?></span></div>
                </div>
                <p><?= nl2br(e((string)$result['result'])) ?></p>
                <?php if (trim((string)($result['interpretation'] ?? '')) !== ''): ?>
                    <h4>Interpretation</h4>
                    <p><?= nl2br(e((string)$result['interpretation'])) ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <p class="text-muted">No laboratory result recorded.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
