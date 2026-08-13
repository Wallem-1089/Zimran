<?php

declare(strict_types=1);

if (!isset($latest) || !is_array($latest)) {
    return;
}
?>
<div class="summary-grid">
    <div class="summary-item"><span class="summary-label">Temperature</span> <span class="summary-value"><?= e((string)($latest['temperature'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Pulse</span> <span class="summary-value"><?= e((string)($latest['pulse'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Respiratory Rate</span> <span class="summary-value"><?= e((string)($latest['respiratory_rate'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Blood Pressure</span> <span class="summary-value"><?= e((string)($latest['blood_pressure'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Oxygen Saturation</span> <span class="summary-value"><?= e((string)($latest['oxygen_saturation'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Weight</span> <span class="summary-value"><?= e((string)($latest['weight'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Height</span> <span class="summary-value"><?= e((string)($latest['height'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">BMI</span> <span class="summary-value"><?= e((string)($latest['bmi'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Blood Glucose</span> <span class="summary-value"><?= e((string)($latest['blood_glucose'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Pain Score</span> <span class="summary-value"><?= e((string)($latest['pain_score'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Recorded By</span> <span class="summary-value"><?= e((string)($latest['recorded_by_name'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Recorded At</span> <span class="summary-value"><?= e((string)($latest['created_at'] ?? '-')) ?></span></div>
</div>
<?php if (!empty($latest['notes'])): ?>
    <h4>Notes</h4>
    <p><?= nl2br(e((string)$latest['notes'])) ?></p>
<?php endif; ?>
