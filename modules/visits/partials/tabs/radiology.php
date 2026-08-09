<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$latest = $latestRadiologyRequest ?? null;
$report = $latestRadiologyResult ?? null;
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$requestSource = $radiologyRequestSource ?? 'Clinical';
?>

<section id="tab-radiology" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Radiology</h2>
                <p>Radiology requests and reports linked to this encounter.</p>
            </div>
            <div>
                <a class="btn-secondary" href="../radiology/index.php">Worklist</a>
                <?php if (!$radiologyTablesReady): ?>
                    <span class="badge badge-warning">Migration required</span>
                <?php elseif (!$canViewRadiology): ?>
                    <span class="badge badge-warning">No radiology permission</span>
                <?php elseif (!$isClosedEncounter && $canCreateRadiologyRequest): ?>
                    <a href="../radiology/request.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        <?= $requestSource === 'Direct' ? 'Create Direct Request' : 'Request Radiology Study' ?>
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

    <?php if (!$radiologyTablesReady): ?>
        <div class="card">
            <p>Radiology tables are not available yet. Apply Migration 027 to enable this section.</p>
        </div>
    <?php elseif (!$canViewRadiology): ?>
        <div class="card alert-warning">
            You do not have permission to view radiology requests.
        </div>
    <?php elseif ($latest === null): ?>
        <div class="card">
            <p class="text-muted">No Radiology requests.</p>
            <?php if (!$isClosedEncounter && $canCreateRadiologyRequest): ?>
                <p>
                    <a href="../radiology/request.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        Request Radiology Study
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Latest Request</h3>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Study Requested</span><span class="summary-value"><?= e((string)($latest['study_requested'] ?? $latest['tests_requested'] ?? '')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Request Source</span><span class="summary-value"><?= e((string)$latest['request_source']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Priority</span><span class="summary-value"><?= e((string)$latest['priority']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Report Status</span><span class="summary-value"><?= e((string)($latest['result_status'] ?? 'Pending')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Requested By</span><span class="summary-value"><?= e((string)($latest['requested_by_name'] ?? 'Unknown')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Requested</span><span class="summary-value"><?= e((string)($latest['created_at'] ?? '-')) ?></span></div>
            </div>
            <div class="form-actions">
                <a href="../radiology/view.php?id=<?= (int)$latest['id'] ?>" class="btn-secondary">View</a>
                <a href="../radiology/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View History</a>
                <?php if (!$isClosedEncounter && $canProcessRadiologyRequest && (string)$latest['status'] === 'Requested'): ?>
                    <form method="post" action="../radiology/start.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$latest['id'] ?>">
                        <button type="submit" class="btn-primary">Start</button>
                    </form>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && ($canEnterRadiologyReport || $canEditRadiologyReport)): ?>
                    <a href="../radiology/report.php?id=<?= (int)$latest['id'] ?>" class="btn-secondary">
                        <?= $report ? 'Edit Report' : 'Enter Report' ?>
                    </a>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && $canCompleteRadiologyRequest): ?>
                    <form method="post" action="../radiology/complete.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$latest['id'] ?>">
                        <button type="submit" class="btn-secondary">Complete</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($report && trim((string)($report['impression'] ?? '')) !== ''): ?>
            <div class="card">
                <h3>Latest Report</h3>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Findings</span><span class="summary-value"><?= e((string)($report['findings'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Impression</span><span class="summary-value"><?= e((string)($report['impression'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Recommendation</span><span class="summary-value"><?= e((string)($report['recommendation'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Reported By</span><span class="summary-value"><?= e((string)($report['result_performed_by_name'] ?? $report['performed_by_name'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Completed At</span><span class="summary-value"><?= e((string)($report['result_completed_at'] ?? '-')) ?></span></div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <p class="text-muted">No radiology report recorded.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
