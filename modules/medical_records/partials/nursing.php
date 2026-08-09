<?php

declare(strict_types=1);
?>
<section class="card">
    <div class="section-heading">
        <h2>Nursing</h2>
        <a class="btn-secondary" href="../nursing/history.php?patient=<?= (int)$patient['id'] ?><?= e($chartContextQuery ?? '') ?>">View History</a>
    </div>
    <?php if (empty($latestNursingAssessment)): ?>
        <p class="text-muted">No nursing assessment recorded.</p>
    <?php else: ?>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Recorded At</span><span class="summary-value"><?= e((string)($latestNursingAssessment['created_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Nurse</span><span class="summary-value"><?= e((string)($latestNursingAssessment['nurse_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Status</span><span class="summary-value"><?= e((string)($latestNursingAssessment['status'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Summary</span><span class="summary-value"><?= e((string)($latestNursingAssessment['summary'] ?? '-')) ?></span></div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>General Condition</th>
                    <th>Observation</th>
                    <th>Pain</th>
                    <th>Mobility</th>
                    <th>Interventions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= nl2br(e((string)($latestNursingAssessment['general_condition'] ?? '-'))) ?></td>
                    <td><?= nl2br(e((string)($latestNursingAssessment['nursing_observation'] ?? '-'))) ?></td>
                    <td><?= nl2br(e((string)($latestNursingAssessment['pain_assessment'] ?? '-'))) ?></td>
                    <td><?= nl2br(e((string)($latestNursingAssessment['mobility'] ?? '-'))) ?></td>
                    <td><?= nl2br(e((string)($latestNursingAssessment['nursing_interventions'] ?? '-'))) ?></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>
</section>
