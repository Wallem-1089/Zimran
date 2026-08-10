<?php

declare(strict_types=1);

if (!isset($patient)) {
    return;
}

$theatreRecord = $latestTheatreRecord ?? null;
?>

<section class="card">
    <div class="card-header">
        <div>
            <h2>Theatre</h2>
            <p>Read-only theatre history for this patient.</p>
        </div>
        <div>
            <a class="btn-secondary" href="../theatre/history.php?patient=<?= (int)$patient['id'] ?>">View History</a>
        </div>
    </div>

    <?php if ($theatreRecord === null): ?>
        <div class="empty-state">No theatre record recorded.</div>
    <?php else: ?>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Date</span><span class="summary-value"><?= e((string)($theatreRecord['created_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Procedure</span><span class="summary-value"><?= e((string)($theatreRecord['procedure_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Surgeon</span><span class="summary-value"><?= e((string)($theatreRecord['surgeon_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span><span class="summary-value"><?= e((string)($theatreRecord['status'] ?? '-')) ?></span></div>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="../theatre/view.php?id=<?= (int)$theatreRecord['id'] ?>">View</a>
            <a class="btn-secondary" href="../visits/workspace.php?id=<?= (int)$theatreRecord['visit_id'] ?>&tab=theatre">Open Encounter</a>
        </div>
    <?php endif; ?>
</section>
