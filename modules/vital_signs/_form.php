<?php

declare(strict_types=1);

$vitalSigns ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Vital Signs';
?>

<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$visit['patient_id'] ?>">
    <?php if (!empty($vitalSigns['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$vitalSigns['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-group"><label for="temperature">Temperature (C)</label><input type="number" step="0.1" min="30" max="45" id="temperature" name="temperature" value="<?= e((string)($vitalSigns['temperature'] ?? '')) ?>"></div>
        <div class="form-group"><label for="pulse">Pulse (bpm)</label><input type="number" step="1" min="20" max="250" id="pulse" name="pulse" value="<?= e((string)($vitalSigns['pulse'] ?? '')) ?>"></div>
        <div class="form-group"><label for="respiratory_rate">Respiratory Rate</label><input type="number" step="1" min="5" max="80" id="respiratory_rate" name="respiratory_rate" value="<?= e((string)($vitalSigns['respiratory_rate'] ?? '')) ?>"></div>
        <div class="form-group"><label for="systolic_bp">Systolic BP</label><input type="number" step="1" min="40" max="260" id="systolic_bp" name="systolic_bp" value="<?= e((string)($vitalSigns['systolic_bp'] ?? '')) ?>"></div>
        <div class="form-group"><label for="diastolic_bp">Diastolic BP</label><input type="number" step="1" min="20" max="160" id="diastolic_bp" name="diastolic_bp" value="<?= e((string)($vitalSigns['diastolic_bp'] ?? '')) ?>"></div>
        <div class="form-group"><label for="oxygen_saturation">Oxygen Saturation (%)</label><input type="number" step="0.1" min="0" max="100" id="oxygen_saturation" name="oxygen_saturation" value="<?= e((string)($vitalSigns['oxygen_saturation'] ?? '')) ?>"></div>
        <div class="form-group"><label for="weight">Weight (kg)</label><input type="number" step="0.1" min="0.1" id="weight" name="weight" value="<?= e((string)($vitalSigns['weight'] ?? '')) ?>"></div>
        <div class="form-group"><label for="height">Height (cm)</label><input type="number" step="0.1" min="0.1" id="height" name="height" value="<?= e((string)($vitalSigns['height'] ?? '')) ?>"></div>
        <div class="form-group"><label for="bmi">BMI</label><input type="number" step="0.1" min="0" id="bmi" name="bmi" value="<?= e((string)($vitalSigns['bmi'] ?? '')) ?>"></div>
        <div class="form-group"><label for="blood_glucose">Blood Glucose</label><input type="number" step="0.1" min="0" id="blood_glucose" name="blood_glucose" value="<?= e((string)($vitalSigns['blood_glucose'] ?? '')) ?>"></div>
        <div class="form-group"><label for="pain_score">Pain Score (0-10)</label><input type="number" step="1" min="0" max="10" id="pain_score" name="pain_score" value="<?= e((string)($vitalSigns['pain_score'] ?? '')) ?>"></div>
    </div>

    <div class="form-group">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="4"><?= e((string)($vitalSigns['notes'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(vitalSignsBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>
