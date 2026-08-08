<?php

declare(strict_types=1);

$department = $department ?? [];
$formAction = $formAction ?? 'save.php';

?>
<form method="POST" action="<?= e($formAction) ?>">
    <?= csrfField() ?>
    <label>Name <input name="department_name" required value="<?= e($department['department_name'] ?? '') ?>"></label>
    <label>Code <input name="department_code" required value="<?= e($department['department_code'] ?? '') ?>"></label>
    <label>Description <textarea name="description"><?= e((string)($department['description'] ?? '')) ?></textarea></label>
    <label>Location <input name="location" value="<?= e($department['location'] ?? '') ?>"></label>
    <label>Contact Extension <input name="contact_extension" value="<?= e($department['contact_extension'] ?? '') ?>"></label>
    <label>Type
        <select name="department_type">
            <?php foreach (['Clinical', 'Administrative', 'Diagnostic', 'Support'] as $type): ?>
                <option value="<?= e($type) ?>" <?= ($department['department_type'] ?? 'Support') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label><input type="checkbox" name="queue_enabled" value="1" <?= !isset($department['queue_enabled']) || !empty($department['queue_enabled']) ? 'checked' : '' ?>> Queue enabled</label>
    <label>Display Order <input type="number" name="display_order" value="<?= (int)($department['display_order'] ?? 0) ?>"></label>
    <button class="btn-primary" type="submit">Save Department</button>
</form>
