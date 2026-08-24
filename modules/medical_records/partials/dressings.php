<?php

declare(strict_types=1);
?>
<section class="card">
    <div class="section-heading">
        <div>
            <h2>Dressing Book</h2>
            <p>Patient dressing and wound-care history across encounters.</p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Dressing Book</button>
            <a class="btn-secondary" href="../nursing/dressings/history.php?patient=<?= (int)$patient['id'] ?>">Open Full History</a>
        </div>
    </div>

    <?php if (empty($dressingHistory)): ?>
        <p class="text-muted">No dressing records found.</p>
    <?php else: ?>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Latest Wound Site</span> <span class="summary-value"><?= e((string)($latestDressingRecord['wound_site'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Latest Date</span> <span class="summary-value"><?= e((string)($latestDressingRecord['created_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Next Dressing</span> <span class="summary-value"><?= e((string)($latestDressingRecord['next_dressing_date'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Total Records</span> <span class="summary-value"><?= count($dressingHistory) ?></span></div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Encounter</th>
                        <th>Wound Site</th>
                        <th>Condition / Dressing</th>
                        <th>Next Dressing</th>
                        <th>Recorded By</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dressingHistory as $record): ?>
                        <tr>
                            <td><?= e((string)($record['created_at'] ?? '-')) ?></td>
                            <td><?= e((string)($record['visit_number'] ?? ('#' . (int)$record['visit_id']))) ?></td>
                            <td><?= e((string)$record['wound_site']) ?></td>
                            <td><?= e((string)($record['summary'] ?? '-')) ?></td>
                            <td><?= e((string)($record['next_dressing_date'] ?? '-')) ?></td>
                            <td><?= e((string)($record['recorded_by_name'] ?? '-')) ?></td>
                            <td class="no-print">
                                <a class="btn-secondary btn-sm" href="../nursing/dressings/view.php?id=<?= (int)$record['id'] ?>">View</a>
                                <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$record['visit_id'] ?>&tab=nursing">Open Encounter</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
