<?php

declare(strict_types=1);

$consultation ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Consultation';

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

<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <?php if (!empty($consultation['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$consultation['id'] ?>">
    <?php endif; ?>

    <?php foreach ($fields as $field => $label): ?>
        <div class="form-group">
            <label for="<?= e($field) ?>"><?= e($label) ?></label>
            <textarea
                id="<?= e($field) ?>"
                name="<?= e($field) ?>"
                rows="<?= in_array($field, ['advice', 'follow_up', 'referral_notes'], true) ? 3 : 5 ?>"
                <?= in_array($field, ['advice', 'follow_up', 'referral_notes'], true) ? '' : 'required' ?>><?= e((string)($consultation[$field] ?? '')) ?></textarea>
        </div>
    <?php endforeach; ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(consultationBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>
