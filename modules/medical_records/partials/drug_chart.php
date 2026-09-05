<?php

declare(strict_types=1);

$medicationAdministrationPreviewRows = array_slice($medicationAdministrationHistory ?? [], 0, 10);
?>
<section class="card">
    <div class="section-heading">
        <div>
            <h2>Drug Chart / MAR</h2>
            <p>Medication administration history across encounters.</p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Drug Chart</button>
            <a class="btn-secondary" href="../nursing/drug_chart/history.php?patient=<?= (int)$patient['id'] ?>">Open Full History</a>
        </div>
    </div>

    <?php if (empty($medicationAdministrationHistory)): ?>
        <p class="text-muted">No drug chart entries found.</p>
    <?php else: ?>
        <?php if (count($medicationAdministrationHistory) > count($medicationAdministrationPreviewRows)): ?>
            <p class="text-muted">Showing latest <?= count($medicationAdministrationPreviewRows) ?> of <?= count($medicationAdministrationHistory) ?> drug chart entries. Open full history to see all entries.</p>
        <?php endif; ?>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Latest Medication</span> <span class="summary-value"><?= e((string)($latestMedicationAdministrationRecord['medication_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Latest Time</span> <span class="summary-value"><?= e((string)($latestMedicationAdministrationRecord['scheduled_time'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Latest Status</span> <span class="summary-value"><?= e((string)($latestMedicationAdministrationRecord['administration_status'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Total Entries</span> <span class="summary-value"><?= count($medicationAdministrationHistory) ?></span></div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Encounter</th>
                        <th>Medication</th>
                        <th>Dose</th>
                        <th>Route</th>
                        <th>Status</th>
                        <th>Administered By</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicationAdministrationPreviewRows as $record): ?>
                        <tr>
                            <td><?= e((string)$record['scheduled_time']) ?></td>
                            <td><?= e((string)($record['visit_number'] ?? ('#' . (int)$record['visit_id']))) ?></td>
                            <td><?= e((string)$record['medication_name']) ?></td>
                            <td><?= e((string)$record['dose_given']) ?></td>
                            <td><?= e((string)($record['route'] ?? '-')) ?></td>
                            <td><?= e((string)$record['administration_status']) ?></td>
                            <td><?= e((string)($record['administered_by_name'] ?? '-')) ?></td>
                            <td class="no-print">
                                <a class="btn-secondary btn-sm" href="../nursing/drug_chart/view.php?id=<?= (int)$record['id'] ?>">View</a>
                                <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$record['visit_id'] ?>&tab=nursing">Open Encounter</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
