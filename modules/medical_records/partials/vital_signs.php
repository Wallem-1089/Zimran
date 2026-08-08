<?php

declare(strict_types=1);
?>
<section class="card">
    <div class="section-heading">
        <h2>Vital Signs</h2>
        <a class="btn-secondary" href="../vital_signs/history.php?patient=<?= (int)$patient['id'] ?><?= e($chartContextQuery ?? '') ?>">View History</a>
    </div>
    <?php if (empty($latestVitalSigns)): ?>
        <p class="text-muted">No vital signs recorded.</p>
    <?php else: ?>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Recorded At</span><span class="summary-value"><?= e((string)($latestVitalSigns['created_at'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Recorded By</span><span class="summary-value"><?= e((string)($latestVitalSigns['recorded_by_name'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Temperature</span><span class="summary-value"><?= e((string)($latestVitalSigns['temperature'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Blood Pressure</span><span class="summary-value"><?= e((string)($latestVitalSigns['blood_pressure'] ?? '-')) ?></span></div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Pulse</th>
                    <th>Respiratory Rate</th>
                    <th>Oxygen Saturation</th>
                    <th>Weight</th>
                    <th>Height</th>
                    <th>BMI</th>
                    <th>Pain Score</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= e((string)($latestVitalSigns['pulse'] ?? '-')) ?></td>
                    <td><?= e((string)($latestVitalSigns['respiratory_rate'] ?? '-')) ?></td>
                    <td><?= e((string)($latestVitalSigns['oxygen_saturation'] ?? '-')) ?></td>
                    <td><?= e((string)($latestVitalSigns['weight'] ?? '-')) ?></td>
                    <td><?= e((string)($latestVitalSigns['height'] ?? '-')) ?></td>
                    <td><?= e((string)($latestVitalSigns['bmi'] ?? '-')) ?></td>
                    <td><?= e((string)($latestVitalSigns['pain_score'] ?? '-')) ?></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>
</section>
