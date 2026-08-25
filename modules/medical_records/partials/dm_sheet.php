<?php

declare(strict_types=1);
?>
<section class="card">
    <div class="section-heading">
        <div>
            <h2>DM Sheet</h2>
            <p>Diabetes monitoring history across encounters.</p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print DM Sheet</button>
            <a class="btn-secondary" href="../nursing/dm_sheet/history.php?patient=<?= (int)$patient['id'] ?>">Open Full History</a>
        </div>
    </div>

    <?php if (empty($diabetesMonitoringHistory)): ?>
        <p class="text-muted">No DM Sheet entries found.</p>
    <?php else: ?>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Latest Blood Glucose</span> <span class="summary-value"><?= e((string)($latestDiabetesMonitoringRecord['blood_glucose'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Latest Meal Status</span> <span class="summary-value"><?= e((string)($latestDiabetesMonitoringRecord['meal_status'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Latest Time</span> <span class="summary-value"><?= e((string)($latestDiabetesMonitoringRecord['recorded_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Total Entries</span> <span class="summary-value"><?= count($diabetesMonitoringHistory) ?></span></div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Recorded At</th>
                        <th>Encounter</th>
                        <th>Blood Glucose</th>
                        <th>Meal Status</th>
                        <th>Insulin Given</th>
                        <th>Recorded By</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diabetesMonitoringHistory as $record): ?>
                        <tr>
                            <td><?= e((string)$record['recorded_at']) ?></td>
                            <td><?= e((string)($record['visit_number'] ?? ('#' . (int)$record['visit_id']))) ?></td>
                            <td><?= e((string)$record['blood_glucose']) ?></td>
                            <td><?= e((string)$record['meal_status']) ?></td>
                            <td><?= e((string)($record['insulin_given'] ?? '-')) ?></td>
                            <td><?= e((string)($record['recorded_by_name'] ?? '-')) ?></td>
                            <td class="no-print">
                                <a class="btn-secondary btn-sm" href="../nursing/dm_sheet/view.php?id=<?= (int)$record['id'] ?>">View</a>
                                <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$record['visit_id'] ?>&tab=nursing">Open Encounter</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
