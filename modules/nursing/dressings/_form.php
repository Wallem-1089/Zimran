<?php

declare(strict_types=1);

$dressingRecord ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Dressing Record';
?>

<form method="post" action="<?= e($action) ?>" class="card">
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

        <div class="form-group">
            <label for="wound_condition">Wound Condition</label>
            <textarea id="wound_condition" name="wound_condition" rows="4"><?= e((string)($dressingRecord['wound_condition'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="dressing_done">Dressing Done</label>
            <textarea id="dressing_done" name="dressing_done" rows="4"><?= e((string)($dressingRecord['dressing_done'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="supplies_used">Supplies Used</label>
            <textarea id="supplies_used" name="supplies_used" rows="3"><?= e((string)($dressingRecord['supplies_used'] ?? '')) ?></textarea>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(dressingBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>
