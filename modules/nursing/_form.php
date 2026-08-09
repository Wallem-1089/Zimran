<?php

declare(strict_types=1);

$nursingAssessment ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Nursing Assessment';
?>

<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$visit['patient_id'] ?>">
    <?php if (!empty($nursingAssessment['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$nursingAssessment['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="general_condition">General Condition</label>
            <textarea id="general_condition" name="general_condition" rows="3"><?= e((string)($nursingAssessment['general_condition'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="nursing_observation">Nursing Observation</label>
            <textarea id="nursing_observation" name="nursing_observation" rows="3"><?= e((string)($nursingAssessment['nursing_observation'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="pain_assessment">Pain Assessment</label>
            <textarea id="pain_assessment" name="pain_assessment" rows="3"><?= e((string)($nursingAssessment['pain_assessment'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="mobility">Mobility</label>
            <textarea id="mobility" name="mobility" rows="3"><?= e((string)($nursingAssessment['mobility'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="nutrition">Nutrition</label>
            <textarea id="nutrition" name="nutrition" rows="3"><?= e((string)($nursingAssessment['nutrition'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="elimination">Elimination</label>
            <textarea id="elimination" name="elimination" rows="3"><?= e((string)($nursingAssessment['elimination'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="skin_assessment">Skin Assessment</label>
            <textarea id="skin_assessment" name="skin_assessment" rows="3"><?= e((string)($nursingAssessment['skin_assessment'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="fall_risk">Fall Risk</label>
            <textarea id="fall_risk" name="fall_risk" rows="3"><?= e((string)($nursingAssessment['fall_risk'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="nursing_interventions">Nursing Interventions</label>
            <textarea id="nursing_interventions" name="nursing_interventions" rows="3"><?= e((string)($nursingAssessment['nursing_interventions'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="patient_response">Patient Response</label>
            <textarea id="patient_response" name="patient_response" rows="3"><?= e((string)($nursingAssessment['patient_response'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="handover_notes">Handover Notes</label>
            <textarea id="handover_notes" name="handover_notes" rows="3"><?= e((string)($nursingAssessment['handover_notes'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label for="additional_notes">Additional Notes</label>
            <textarea id="additional_notes" name="additional_notes" rows="3"><?= e((string)($nursingAssessment['additional_notes'] ?? '')) ?></textarea>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(nursingBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>
