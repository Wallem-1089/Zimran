<?php

declare(strict_types=1);

if (!isset($theatre)) {
    $theatre = [];
}
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);

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

<form method="post" action="<?= e($action) ?>" class="card form-card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)($theatre['visit_id'] ?? 0) ?>">
    <?php if (!empty($theatre['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$theatre['id'] ?>">
    <?php endif; ?>

    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Theatre Entry Mode'); ?>

    <div class="form-grid">
        <?php foreach ($fields as $field => $label): ?>
            <?php if ($field === 'procedure_name'): ?>
                <div class="form-group">
                    <label for="<?= e($field) ?>"><?= e($label) ?></label>
                    <input
                        type="text"
                        id="<?= e($field) ?>"
                        name="<?= e($field) ?>"
                        value="<?= e((string)($theatre[$field] ?? '')) ?>"
                        required
                    >
                </div>
            <?php else: ?>
                <?php hmsRenderHandwritingTextarea(
                    $field,
                    $label,
                    (string)($theatre[$field] ?? ''),
                    in_array($field, ['procedure_details','postoperative_plan','anaesthesia_notes'], true) ? 6 : 4,
                    false,
                    $enableWritingMode
                ); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel ?? 'Save Theatre Record') ?></button>
        <a class="btn-secondary" href="<?= e($cancelUrl ?? theatreBackToWorkspace((int)($theatre['visit_id'] ?? 0))) ?>">Cancel</a>
    </div>
</form>

<?php hmsRenderHandwritingScript($enableWritingMode); ?>
