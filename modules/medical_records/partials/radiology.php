<?php

declare(strict_types=1);

if (!isset($patient)) {
    return;
}

$latest = $latestRadiologyRequest ?? null;
?>

<section class="card">
    <div class="card-header">
        <div>
            <h2>Radiology</h2>
            <p>Patient radiology requests and reports.</p>
        </div>
        <?php if (!empty($visitId) && isset($visit) && $permissionService->canCreateRadiologyRequest($visit, $currentUser, 'Clinical')): ?>
            <a class="btn-primary" href="../radiology/request.php?visit=<?= (int)$visitId ?>&source=Clinical">Request Radiology Study</a>
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
    <?php if (empty($radiologyHistory)): ?>
        <p class="text-muted">No radiology requests recorded.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Study Requested</th>
                        <th>Source</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($radiologyHistory as $request): ?>
                        <tr>
                            <td>#<?= (int)$request['id'] ?></td>
                            <td><?= e((string)($request['study_requested'] ?? $request['tests_requested'] ?? '')) ?></td>
                            <td><?= e((string)$request['request_source']) ?></td>
                            <td><?= e((string)$request['priority']) ?></td>
                            <td><?= e((string)$request['status']) ?></td>
                            <td><?= e((string)($request['created_at'] ?? '-')) ?></td>
                            <td>
                                <a class="btn-secondary btn-sm" href="../radiology/view.php?id=<?= (int)$request['id'] ?>">View</a>
                                <a class="btn-secondary btn-sm" href="../radiology/history.php?patient=<?= (int)$patient['id'] ?>">History</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
