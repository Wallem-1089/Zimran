<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$latest = $latestPopRequest ?? null;
$record = $latestPopRecord ?? null;
$isClosedEncounter = in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true);
$requestSource = $popRequestSource ?? 'Clinical';
$requestClosed = $latest !== null && in_array((string)($latest['status'] ?? ''), ['Completed', 'Cancelled'], true);
?>

<section id="tab-pop" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>POP / Casting</h2>
                <p>POP and casting requests, procedure notes, materials used, aftercare, and remarks.</p>
            </div>
            <div class="form-actions">
                <?php if (!empty($canOpenPopWorklist)): ?>
                    <a class="btn-secondary" href="../pop/index.php">Worklist</a>
                <?php endif; ?>
                <?php if (!($popTablesReady ?? false)): ?>
                    <span class="badge badge-warning">Migration required</span>
                <?php elseif (!($canViewPop ?? false)): ?>
                    <span class="badge badge-warning">No POP permission</span>
                <?php elseif (!$isClosedEncounter && !empty($canCreatePopRequest)): ?>
                    <a href="../pop/request.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        <?= $requestSource === 'Direct' ? 'Create Direct POP Request' : 'Request POP / Casting' ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Encounter</span> <span class="summary-value"><?= e((string)($visit['visit_number'] ?? ('#' . (int)$visit['id']))) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span> <span class="summary-value"><?= e((string)$patient['hospital_number']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Latest Request</span> <span class="summary-value"><?= e((string)($latest['created_at'] ?? 'Not recorded')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)($latest['status'] ?? 'Not recorded')) ?></span></div>
        </div>
    </div>

    <?php if (!($popTablesReady ?? false)): ?>
        <div class="card"><p>POP tables are not available yet. Apply Migration 059 to enable this section.</p></div>
    <?php elseif (!($canViewPop ?? false)): ?>
        <div class="card alert-warning">You do not have permission to view POP requests.</div>
    <?php elseif ($latest === null): ?>
        <div class="card">
            <p class="text-muted">No POP requests.</p>
            <?php if (!$isClosedEncounter && !empty($canCreatePopRequest)): ?>
                <p>
                    <a href="../pop/request.php?visit=<?= (int)$visit['id'] ?>&source=<?= e($requestSource) ?>" class="btn-primary">
                        <?= $requestSource === 'Direct' ? 'Create Direct POP Request' : 'Request POP / Casting' ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Latest POP Request</h3>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Procedure</span> <span class="summary-value"><?= e((string)($latest['procedure_requested'] ?? 'POP / Casting')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Source</span> <span class="summary-value"><?= e((string)$latest['request_source']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Priority</span> <span class="summary-value"><?= e((string)$latest['priority']) ?></span></div>
                <div class="summary-item"><span class="summary-label">Record Status</span> <span class="summary-value"><?= e((string)($latest['record_status'] ?? 'Pending')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Requested By</span> <span class="summary-value"><?= e((string)($latest['requested_by_name'] ?? 'Unknown')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Requested</span> <span class="summary-value"><?= e((string)($latest['created_at'] ?? '-')) ?></span></div>
            </div>

            <?php if (trim((string)($latest['clinical_indication'] ?? '')) !== ''): ?>
                <h4>Clinical Indication / Reason</h4>
                <p><?= nl2br(e((string)$latest['clinical_indication'])) ?></p>
            <?php endif; ?>

            <div class="form-actions">
                <a href="../pop/view.php?id=<?= (int)$latest['id'] ?>" class="btn-secondary">View</a>
                <a href="../pop/history.php?visit=<?= (int)$visit['id'] ?>" class="btn-secondary">View History</a>
                <?php if (!$isClosedEncounter && !$requestClosed && !empty($canProcessPopRequest) && (string)$latest['status'] === 'Requested'): ?>
                    <form method="post" action="../pop/start.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$latest['id'] ?>"><button type="submit" class="btn-primary">Start</button></form>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && !$requestClosed && (!empty($canRecordPopProcedure) || !empty($canEditPopRecord))): ?>
                    <a href="../pop/record.php?id=<?= (int)$latest['id'] ?>" class="btn-secondary"><?= $record && !empty($record['record_id']) ? 'Edit POP Record' : 'Record POP Procedure' ?></a>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && !$requestClosed && !empty($canCompletePopRequest)): ?>
                    <form method="post" action="../pop/complete.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$latest['id'] ?>"><button type="submit" class="btn-secondary">Complete</button></form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h3>Latest POP / Casting Record</h3>
            <?php if ($record && !empty($record['record_id'])): ?>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Cast Type</span> <span class="summary-value"><?= e((string)($record['cast_type'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Body Part</span> <span class="summary-value"><?= e((string)($record['body_part'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Performed By</span> <span class="summary-value"><?= e((string)($record['performed_by_name'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Completed At</span> <span class="summary-value"><?= e((string)($record['record_completed_at'] ?? '-')) ?></span></div>
                </div>
                <h4>Procedure Notes</h4><p><?= nl2br(e((string)($record['procedure_notes'] ?? ''))) ?></p>
                <h4>Materials Used</h4><p><?= trim((string)($record['materials_used'] ?? '')) === '' ? '<span class="text-muted">No materials recorded.</span>' : nl2br(e((string)$record['materials_used'])) ?></p>
                <h4>Aftercare</h4><p><?= trim((string)($record['aftercare_instructions'] ?? '')) === '' ? '<span class="text-muted">No aftercare instructions recorded.</span>' : nl2br(e((string)$record['aftercare_instructions'])) ?></p>
                <h4>Remarks</h4><p><?= trim((string)($record['remarks'] ?? '')) === '' ? '<span class="text-muted">No remarks recorded.</span>' : nl2br(e((string)$record['remarks'])) ?></p>
            <?php else: ?>
                <p class="text-muted">No POP procedure record yet.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
