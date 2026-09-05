<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$latest = $latestEcgRequest ?? null;
$report = $latestEcgReport ?? null;
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$requestSource = $ecgRequestSource ?? 'Clinical';
$requestClosed = $latest !== null && in_array((string)($latest['status'] ?? ''), ['Completed', 'Cancelled'], true);
?>

<section id="tab-ecg" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>ECG</h2>
                <p>ECG requests, scanned ECG charts, notes, and remarks linked to this encounter.</p>
            </div>
            <div class="form-actions">
                <?php if (!empty($canOpenEcgWorklist)): ?>
                    <a class="btn-secondary" href="../ecg/index.php">Worklist</a>
                <?php endif; ?>
                <?php if (!($ecgTablesReady ?? false)): ?>
                    <span class="badge badge-warning">Migration required</span>
                <?php elseif (!($canViewEcg ?? false)): ?>
                    <span class="badge badge-warning">No ECG permission</span>
                <?php elseif (!$isClosedEncounter && !empty($canCreateEcgRequest)): ?>
                    <a href="../ecg/request.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        <?= $requestSource === 'Direct' ? 'Create Direct ECG Request' : 'Request ECG' ?>
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

    <?php if (!($ecgTablesReady ?? false)): ?>
        <div class="card">
            <p>ECG tables are not available yet. Apply Migration 058 to enable this section.</p>
        </div>
    <?php elseif (!($canViewEcg ?? false)): ?>
        <div class="card alert-warning">
            You do not have permission to view ECG requests.
        </div>
    <?php elseif ($latest === null): ?>
        <div class="card">
            <p class="text-muted">No ECG requests.</p>
            <?php if (!$isClosedEncounter && !empty($canCreateEcgRequest)): ?>
                <p>
                    <a href="../ecg/request.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        <?= $requestSource === 'Direct' ? 'Create Direct ECG Request' : 'Request ECG' ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Latest ECG Request</h3>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Study</span> <span class="summary-value"><?= e((string)($latest['study_requested'] ?? 'ECG')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Source</span> <span class="summary-value"><?= e((string)$latest['request_source']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Priority</span> <span class="summary-value"><?= e((string)$latest['priority']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Chart Status</span> <span class="summary-value"><?= e((string)($latest['result_status'] ?? 'Pending')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Requested By</span> <span class="summary-value"><?= e((string)($latest['requested_by_name'] ?? 'Unknown')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Requested</span> <span class="summary-value"><?= e((string)($latest['created_at'] ?? '-')) ?></span></div>
            </div>

            <?php if (trim((string)($latest['clinical_indication'] ?? '')) !== ''): ?>
                <h4>Clinical Indication</h4>
                <p><?= nl2br(e((string)$latest['clinical_indication'])) ?></p>
            <?php endif; ?>

            <div class="form-actions">
                <a href="../ecg/view.php?id=<?= (int)$latest['id'] ?>" class="btn-secondary">View</a>
                <a href="../ecg/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View History</a>
                <?php if (!$isClosedEncounter && !$requestClosed && !empty($canProcessEcgRequest) && (string)$latest['status'] === 'Requested'): ?>
                    <form method="post" action="../ecg/start.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$latest['id'] ?>">
                        <button type="submit" class="btn-primary">Start</button>
                    </form>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && !$requestClosed && (!empty($canUploadEcgChart) || !empty($canEditEcgReport))): ?>
                    <a href="../ecg/report.php?id=<?= (int)$latest['id'] ?>" class="btn-secondary">
                        <?= $report && !empty($report['report_id']) ? 'Edit ECG Chart/Notes' : 'Upload ECG Chart' ?>
                    </a>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && !$requestClosed && !empty($canCompleteEcgRequest)): ?>
                    <form method="post" action="../ecg/complete.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$latest['id'] ?>">
                        <button type="submit" class="btn-secondary">Complete</button>
                    </form>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && !$requestClosed && !empty($billingRequestsReady) && !empty($canCreateBillingRequest)): ?>
                    <a href="../billing/request_create.php?visit=<?= (int)$visit['id'] ?>&source_module=ECG&source_record_id=<?= (int)$latest['id'] ?>&description=<?= rawurlencode('ECG: ' . (string)($latest['study_requested'] ?? '')) ?>" class="btn-secondary">Request Billing</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($report && !empty($report['report_id'])): ?>
            <div class="card">
                <h3>Latest ECG Chart / Notes</h3>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Chart</span> <span class="summary-value">
                        <?php if (!empty($report['chart_stored_path'])): ?>
                            <a href="../ecg/download_chart.php?id=<?= (int)$latest['id'] ?>" target="_blank" rel="noopener">Open scanned ECG chart</a>
                        <?php else: ?>
                            Not uploaded
                        <?php endif; ?>
                    </span></div>
                    <div class="summary-item"><span class="summary-label">Performed By</span> <span class="summary-value"><?= e((string)($report['performed_by_name'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Completed At</span> <span class="summary-value"><?= e((string)($report['report_completed_at'] ?? '-')) ?></span></div>
                </div>
                <h4>Notes</h4>
                <p><?= trim((string)($report['notes'] ?? '')) === '' ? '<span class="text-muted">No ECG notes recorded.</span>' : nl2br(e((string)$report['notes'])) ?></p>
                <h4>Remarks</h4>
                <p><?= trim((string)($report['remarks'] ?? '')) === '' ? '<span class="text-muted">No ECG remarks recorded.</span>' : nl2br(e((string)$report['remarks'])) ?></p>
            </div>
        <?php else: ?>
            <div class="card">
                <p class="text-muted">No scanned ECG chart or ECG notes recorded.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
