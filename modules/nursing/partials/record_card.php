<?php

declare(strict_types=1);

if (!isset($latest) || !is_array($latest)) {
    return;
}
?>
<div class="summary-grid">
    <div class="summary-item"><span class="summary-label">Status</span><span class="summary-value"><?= e((string)($latest['status'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Nurse</span><span class="summary-value"><?= e((string)($latest['nurse_name'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Recorded By</span><span class="summary-value"><?= e((string)($latest['created_by_name'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Recorded At</span><span class="summary-value"><?= e((string)($latest['created_at'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Completed By</span><span class="summary-value"><?= e((string)($latest['completed_by_name'] ?? '-')) ?></span></div>
    <div class="summary-item"><span class="summary-label">Completed At</span><span class="summary-value"><?= e((string)($latest['completed_at'] ?? '-')) ?></span></div>
</div>
<table>
    <tbody>
        <tr><th>General Condition</th><td><?= nl2br(e((string)($latest['general_condition'] ?? '-'))) ?></td></tr>
        <tr><th>Nursing Observation</th><td><?= nl2br(e((string)($latest['nursing_observation'] ?? '-'))) ?></td></tr>
        <tr><th>Pain Assessment</th><td><?= nl2br(e((string)($latest['pain_assessment'] ?? '-'))) ?></td></tr>
        <tr><th>Mobility</th><td><?= nl2br(e((string)($latest['mobility'] ?? '-'))) ?></td></tr>
        <tr><th>Nutrition</th><td><?= nl2br(e((string)($latest['nutrition'] ?? '-'))) ?></td></tr>
        <tr><th>Elimination</th><td><?= nl2br(e((string)($latest['elimination'] ?? '-'))) ?></td></tr>
        <tr><th>Skin Assessment</th><td><?= nl2br(e((string)($latest['skin_assessment'] ?? '-'))) ?></td></tr>
        <tr><th>Fall Risk</th><td><?= nl2br(e((string)($latest['fall_risk'] ?? '-'))) ?></td></tr>
        <tr><th>Nursing Interventions</th><td><?= nl2br(e((string)($latest['nursing_interventions'] ?? '-'))) ?></td></tr>
        <tr><th>Patient Response</th><td><?= nl2br(e((string)($latest['patient_response'] ?? '-'))) ?></td></tr>
        <tr><th>Handover Notes</th><td><?= nl2br(e((string)($latest['handover_notes'] ?? '-'))) ?></td></tr>
        <tr><th>Additional Notes</th><td><?= nl2br(e((string)($latest['additional_notes'] ?? '-'))) ?></td></tr>
    </tbody>
</table>
