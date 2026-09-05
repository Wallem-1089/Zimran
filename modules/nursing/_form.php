<?php

declare(strict_types=1);

$nursingAssessment ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Nursing Assessment';
$nursingConfiguredFields ??= [];
$nursingConfiguredValues ??= [];
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>

<form method="post" action="<?= e($action) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$visit['patient_id'] ?>">
    <?php if (!empty($nursingAssessment['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$nursingAssessment['id'] ?>">
    <?php endif; ?>

    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Nursing Entry Mode'); ?>
    <div class="form-grid">
        <?php foreach ([
            'general_condition' => 'General Condition',
            'nursing_observation' => 'Nursing Observation',
            'pain_assessment' => 'Pain Assessment',
            'mobility' => 'Mobility',
            'nutrition' => 'Nutrition',
            'elimination' => 'Elimination',
            'skin_assessment' => 'Skin Assessment',
            'fall_risk' => 'Fall Risk',
            'nursing_interventions' => 'Nursing Interventions',
            'patient_response' => 'Patient Response',
            'handover_notes' => 'Handover Notes',
            'additional_notes' => 'Additional Notes',
        ] as $field => $label): ?>
            <?php hmsRenderHandwritingTextarea($field, $label, (string)($nursingAssessment[$field] ?? ''), 3, false, $enableWritingMode); ?>
        <?php endforeach; ?>
    </div>

    <?php hmsRenderConfiguredFields($nursingConfiguredFields, $nursingConfiguredValues); ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(nursingBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>
<?php hmsRenderHandwritingScript($enableWritingMode); ?>
