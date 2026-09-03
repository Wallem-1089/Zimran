<?php

declare(strict_types=1);

$dressingRecord ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Dressing Record';
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>

<form method="post" action="<?= e($action) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$visit['patient_id'] ?>">
    <?php if (!empty($dressingRecord['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$dressingRecord['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="wound_site">Wound Site</label>
            <input id="wound_site" name="wound_site" required maxlength="255" value="<?= e((string)($dressingRecord['wound_site'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="next_dressing_date">Next Dressing Date</label>
            <input id="next_dressing_date" name="next_dressing_date" type="date" value="<?= e((string)($dressingRecord['next_dressing_date'] ?? '')) ?>">
        </div>

        <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Dressing Record Entry Mode'); ?>
        <?php hmsRenderHandwritingTextarea('wound_condition', 'Wound Condition', (string)($dressingRecord['wound_condition'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('dressing_done', 'Dressing Done', (string)($dressingRecord['dressing_done'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('supplies_used', 'Supplies Used', (string)($dressingRecord['supplies_used'] ?? ''), 3, false, $enableWritingMode); ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(dressingBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>
<?php hmsRenderHandwritingScript($enableWritingMode); ?>
