<?php

declare(strict_types=1);

if (!isset($patient)) {
    return;
}

$latest = $latestPhysiotherapyRecord ?? null;
?>

<section class="card">
    <div class="card-header">
        <div>
            <h2>Physiotherapy</h2>
            <p>Patient physiotherapy records and sessions.</p>
        </div>
        <?php if (!empty($visitId) && isset($visit) && $permissionService->canCreatePhysiotherapyRequest($visit, $currentUser, 'Clinical')): ?>
            <a class="btn-primary" href="../physiotherapy/request.php?visit=<?= (int)$visitId ?>&source=Clinical">Refer to Physiotherapy</a>
        <?php endif; ?>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <span class="summary-label">Patient</span>
            <span class="summary-value"><?= e((string)($patient['first_name'] . ' ' . $patient['last_name'])) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Hospital Number</span>
            <span class="summary-value"><?= e((string)$patient['hospital_number']) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Latest Record</span>
            <span class="summary-value"><?= e((string)($latest['created_at'] ?? 'Not recorded')) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Status</span>
            <span class="summary-value"><?= e((string)($latest['status'] ?? 'Not recorded')) ?></span>
        </div>
    </div>
</section>

<section class="card">
    <?php if (empty($physiotherapyHistory)): ?>
        <p class="text-muted">No physiotherapy records recorded.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Presenting Problem</th>
                        <th>Status</th>
                        <th>Sessions</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($physiotherapyHistory as $record): ?>
                        <tr>
                            <td><?= e((string)($record['created_at'] ?? '-')) ?></td>
                            <td><?= e((string)$record['record_source']) ?></td>
                            <td><?= e((string)($record['presenting_problem'] ?? $record['summary'] ?? '')) ?></td>
                            <td><?= e((string)$record['status']) ?></td>
                            <td><?= e((string)($record['session_count'] ?? 0)) ?></td>
                            <td>
                                <a class="btn-secondary btn-sm" href="../physiotherapy/view.php?id=<?= (int)$record['id'] ?>">View</a>
                                <a class="btn-secondary btn-sm" href="../physiotherapy/history.php?patient=<?= (int)$patient['id'] ?>">History</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
