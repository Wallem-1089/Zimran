<?php

declare(strict_types=1);

if (!isset($visit, $patient)) {
    return;
}

$theatreRecord = $latestTheatreRecord ?? null;
$theatreHistory = $theatreHistory ?? [];
$canCreateTheatre = $canCreateTheatre ?? false;
$canEditTheatre = $canEditTheatre ?? false;
$canCompleteTheatre = $canCompleteTheatre ?? false;
?>

<section id="tab-theatre" class="workspace-tab">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Theatre</h2>
                <p>Simple encounter-linked theatre record and operative notes.</p>
            </div>
            <div>
                <?php if ($theatreRecord === null && $canCreateTheatre): ?>
                    <a class="btn-primary" href="../theatre/create.php?visit=<?= (int)$visit['id'] ?>">Start Theatre Record</a>
                <?php elseif ($theatreRecord !== null): ?>
                    <a class="btn-secondary" href="../theatre/view.php?id=<?= (int)$theatreRecord['id'] ?>">Open Theatre</a>
                    <?php if ((string)($theatreRecord['status'] ?? '') === 'Draft' && $canEditTheatre): ?>
                        <a class="btn-primary" href="../theatre/edit.php?id=<?= (int)$theatreRecord['id'] ?>">Continue/Edit</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">Encounter</span>
                <span class="summary-value">#<?= (int)$visit['id'] ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Hospital Number</span>
                <span class="summary-value"><?= e($patient['hospital_number']) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Theatre Status</span>
                <span class="summary-value"><?= e((string)($theatreRecord['status'] ?? 'No Theatre record')) ?></span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Procedure</span>
                <span class="summary-value"><?= e((string)($theatreRecord['procedure_name'] ?? '-')) ?></span>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Theatre Record</h3>
        <?php if ($theatreRecord === null): ?>
            <div class="empty-state">No Theatre record.</div>
        <?php else: ?>
            <div class="summary-grid">
                <div class="summary-item"><span class="summary-label">Surgeon</span> <span class="summary-value"><?= e((string)($theatreRecord['surgeon_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Created By</span> <span class="summary-value"><?= e((string)($theatreRecord['created_by_name'] ?? '-')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Completed By</span> <span class="summary-value"><?= e((string)($theatreRecord['completed_by_name'] ?? 'Not completed')) ?></span></div>
                <div class="summary-item"><span class="summary-label">Completed At</span> <span class="summary-value"><?= e((string)($theatreRecord['completed_at'] ?? 'Not completed')) ?></span></div>
            </div>
            <div class="form-actions">
                <a class="btn-secondary" href="../theatre/view.php?id=<?= (int)$theatreRecord['id'] ?>">View</a>
                <a class="btn-secondary" href="../theatre/history.php?patient=<?= (int)$patient['id'] ?>">History</a>
                <?php if ((string)($theatreRecord['status'] ?? '') === 'Draft' && $canCompleteTheatre): ?>
                    <form method="post" action="../theatre/complete.php" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int)$theatreRecord['id'] ?>">
                        <button class="btn-primary" type="submit">Complete</button>
                    </form>
                <?php endif; ?>
                <?php if (!$isClosedEncounter && !empty($billingRequestsReady) && !empty($canCreateBillingRequest)): ?>
                    <a class="btn-secondary" href="../billing/request_create.php?visit=<?= (int)$visit['id'] ?>&source_module=Theatre&source_record_id=<?= (int)$theatreRecord['id'] ?>&description=<?= rawurlencode('Theatre: ' . (string)($theatreRecord['procedure_name'] ?? '')) ?>">Request Billing</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Recent Theatre History</h3>
        <?php if ($theatreHistory === []): ?>
            <p class="text-muted">No theatre history recorded for this encounter.</p>
        <?php else: ?>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Procedure</th>
                        <th>Surgeon</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($theatreHistory, 0, 5) as $record): ?>
                        <tr>
                            <td><?= e((string)($record['created_at'] ?? '-')) ?></td>
                            <td><?= e((string)($record['procedure_name'] ?? '-')) ?></td>
                            <td><?= e((string)($record['surgeon_name'] ?? '-')) ?></td>
                            <td><?= e((string)($record['status'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
