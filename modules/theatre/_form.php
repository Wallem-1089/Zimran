<?php

declare(strict_types=1);

if (!isset($theatre)) {
    $theatre = [];
}

$fields = [
    'procedure_name' => 'Procedure Name',
    'indication' => 'Indication',
    'preoperative_notes' => 'Preoperative Notes',
    'procedure_details' => 'Procedure Details',
    'findings' => 'Findings',
    'complications' => 'Complications',
    'postoperative_notes' => 'Postoperative Notes',
    'postoperative_plan' => 'Postoperative Plan',
    'anaesthesia_notes' => 'Anaesthesia Notes',
];
?>

<form method="post" action="<?= e($action) ?>" class="card form-card">
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)($theatre['visit_id'] ?? 0) ?>">
    <?php if (!empty($theatre['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$theatre['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
        <?php foreach ($fields as $field => $label): ?>
            <div class="form-group <?= in_array($field, ['procedure_details','preoperative_notes','indication','findings','complications','postoperative_notes','postoperative_plan','anaesthesia_notes'], true) ? 'full-width' : '' ?>">
                <label for="<?= e($field) ?>"><?= e($label) ?></label>
                <?php if ($field === 'procedure_name'): ?>
                    <input
                        type="text"
                        id="<?= e($field) ?>"
                        name="<?= e($field) ?>"
                        value="<?= e((string)($theatre[$field] ?? '')) ?>"
                        required
                    >
                <?php else: ?>
                    <textarea
                        id="<?= e($field) ?>"
                        name="<?= e($field) ?>"
                        rows="<?= in_array($field, ['procedure_details','postoperative_plan','anaesthesia_notes'], true) ? 6 : 4 ?>"
                    ><?= e((string)($theatre[$field] ?? '')) ?></textarea>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel ?? 'Save Theatre Record') ?></button>
        <a class="btn-secondary" href="<?= e($cancelUrl ?? theatreBackToWorkspace((int)($theatre['visit_id'] ?? 0))) ?>">Cancel</a>
    </div>
</form>

