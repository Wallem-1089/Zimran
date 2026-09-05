<?php

declare(strict_types=1);

$consultation ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Consultation';
$enableWritingMode ??= false;

$fields = [
    'presenting_complaint' => 'Presenting Complaint',
    'history_of_presenting_complaint' => 'History of Presenting Complaint',
    'examination_findings' => 'Examination Findings',
    'assessment' => 'Assessment',
    'diagnosis' => 'Diagnosis',
    'treatment_plan' => 'Treatment Plan',
    'advice' => 'Advice',
    'follow_up' => 'Follow Up',
    'referral_notes' => 'Referral Notes'
];
?>

<form method="post" action="<?= e($action) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <?php if (!empty($consultation['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$consultation['id'] ?>">
    <?php endif; ?>

    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Consultation Entry Mode'); ?>

<?php foreach ($fields as $field => $label): ?>
        <?php hmsRenderHandwritingTextarea(
            $field,
            $label,
            (string)($consultation[$field] ?? ''),
            in_array($field, ['advice', 'follow_up', 'referral_notes'], true) ? 3 : 5,
            $field === 'presenting_complaint',
            $enableWritingMode
        ); ?>
    <?php endforeach; ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(consultationBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>

<?php hmsRenderHandwritingScript($enableWritingMode); ?>
