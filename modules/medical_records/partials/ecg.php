<?php

declare(strict_types=1);

if (!isset($patient)) {
    return;
}

$latest = $latestEcgRequest ?? null;
$ecgPreviewRows = array_slice($ecgHistory ?? [], 0, 10);
?>

<section class="card">
    <div class="card-header">
        <div>
            <h2>ECG</h2>
            <p>Patient ECG requests, uploaded charts, notes, and remarks.</p>
        </div>
        <?php if (!empty($visitId) && isset($visit) && $permissionService->canCreateEcgRequest($visit, $currentUser, 'Clinical')): ?>
            <a class="btn-primary" href="../ecg/request.php?visit=<?= (int)$visitId ?>&source=Clinical">Request ECG</a>
        <?php endif; ?>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <span class="summary-label">Patient</span>
            <span class="summary-value"><?= e(trim((string)(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''))) ?: '-') ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Hospital Number</span>
            <span class="summary-value"><?= e((string)($patient['hospital_number'] ?? '-')) ?></span>
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
</section>

<section class="card">
    <?php if (empty($ecgHistory)): ?>
        <p class="text-muted">No ECG records found.</p>
    <?php else: ?>
        <?php if (count($ecgHistory) > count($ecgPreviewRows)): ?>
            <p class="text-muted">Showing latest <?= count($ecgPreviewRows) ?> of <?= count($ecgHistory) ?> ECG records. Open history to see all records.</p>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Study</th>
                        <th>Source</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Chart</th>
                        <th>Requested</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ecgPreviewRows as $request): ?>
                        <tr>
                            <td>#<?= (int)$request['id'] ?></td>
                            <td><?= e((string)($request['study_requested'] ?? 'ECG')) ?></td>
                            <td><?= e((string)($request['request_source'] ?? '-')) ?></td>
                            <td><?= e((string)($request['priority'] ?? '-')) ?></td>
                            <td><?= e((string)($request['status'] ?? '-')) ?></td>
                            <td><?= !empty($request['chart_stored_path']) ? 'Uploaded' : 'Pending' ?></td>
                            <td><?= e((string)($request['created_at'] ?? '-')) ?></td>
                            <td>
                                <a class="btn-secondary btn-sm" href="../ecg/view.php?id=<?= (int)$request['id'] ?>">View</a>
                                <a class="btn-secondary btn-sm" href="../ecg/history.php?patient=<?= (int)$patient['id'] ?>">History</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
