<?php

declare(strict_types=1);

$confirmedProblems = $workspaceProblemSummary['data']['active_confirmed'] ?? [];
$severeProblems = $workspaceProblemSummary['data']['severe_active_confirmed'] ?? [];
$historyEntries = $workspaceMedicalHistorySummary['data']['entries'] ?? [];
?>
<section class="card" aria-labelledby="longitudinal-summary-title">
    <h2 id="longitudinal-summary-title">Longitudinal Clinical Summary</h2>
    <?php if ($canViewProblemList): ?>
        <h3>Active Confirmed Problems</h3>
        <?php if ($confirmedProblems === []): ?><p class="text-muted">No active confirmed problems are recorded.</p><?php else: ?><ul><?php foreach ($confirmedProblems as $problem): ?><li><strong><?= e($problem['problem_name']) ?></strong> — <?= e($problem['severity']) ?><?= in_array((int)$problem['id'], array_map('intval', array_column($severeProblems, 'id')), true) ? ' (clinically important)' : '' ?></li><?php endforeach; ?></ul><?php endif; ?>
        <a href="../medical_records/chart.php?patient=<?= (int)$patient['id'] ?>&tab=problems&visit=<?= (int)$visitId ?>">View full Problem List</a>
    <?php endif; ?>
    <?php if ($canViewMedicalHistory): ?>
        <h3>Relevant Structured History</h3>
        <?php if ($historyEntries === []): ?><p class="text-muted">No structured medical history is recorded.</p><?php else: ?><ul><?php foreach ($historyEntries as $entry): ?><li><strong><?= e($entry['history_type']) ?>:</strong> <?= e($entry['title']) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <a href="../medical_records/chart.php?patient=<?= (int)$patient['id'] ?>&tab=medical_history&visit=<?= (int)$visitId ?>">View full Medical History</a>
    <?php endif; ?>
</section>
