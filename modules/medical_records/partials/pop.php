<?php

declare(strict_types=1);

if (!isset($patient)) {
    return;
}

$latest = $latestPopRequest ?? null;
$popPreviewRows = array_slice($popHistory ?? [], 0, 10);
?>

<section class="card">
    <div class="card-header">
        <div>
            <h2>POP</h2>
            <p>Patient POP/casting requests and procedure records.</p>
        </div>
        <?php if (!empty($visitId) && isset($visit) && $permissionService->canCreatePopRequest($visit, $currentUser, 'Clinical')): ?>
            <a class="btn-primary" href="../pop/request.php?visit=<?= (int)$visitId ?>&source=Clinical">Request POP / Casting</a>
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
    <?php if (empty($popHistory)): ?>
        <p class="text-muted">No POP records found.</p>
    <?php else: ?>
        <?php if (count($popHistory) > count($popPreviewRows)): ?>
            <p class="text-muted">Showing latest <?= count($popPreviewRows) ?> of <?= count($popHistory) ?> POP records. Open history to see all records.</p>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Procedure</th>
                        <th>Body Part</th>
                        <th>Source</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Performed By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($popPreviewRows as $request): ?>
                        <tr>
                            <td>#<?= (int)$request['id'] ?></td>
                            <td><?= e((string)($request['procedure_requested'] ?? 'POP / Casting')) ?></td>
                            <td><?= e((string)($request['body_part'] ?? '-')) ?></td>
                            <td><?= e((string)($request['request_source'] ?? '-')) ?></td>
                            <td><?= e((string)($request['priority'] ?? '-')) ?></td>
                            <td><?= e((string)($request['status'] ?? '-')) ?></td>
                            <td><?= e((string)($request['performed_by_name'] ?? '-')) ?></td>
                            <td>
                                <a class="btn-secondary btn-sm" href="../pop/view.php?id=<?= (int)$request['id'] ?>">View</a>
                                <a class="btn-secondary btn-sm" href="../pop/history.php?patient=<?= (int)$patient['id'] ?>">History</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
