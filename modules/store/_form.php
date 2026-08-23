<?php

declare(strict_types=1);

$item ??= [];
$departments ??= [];
$billableItems ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Item';
?>

<form method="post" action="<?= e($action) ?>" class="card form-card">
    <?= csrfField() ?>
    <?php if (!empty($item['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="item_code">Item Code</label>
            <input type="text" id="item_code" name="item_code" maxlength="30" required value="<?= e((string)($item['item_code'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="item_name">Item Name</label>
            <input type="text" id="item_name" name="item_name" maxlength="255" required value="<?= e((string)($item['item_name'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="category">Category</label>
            <input type="text" id="category" name="category" maxlength="100" required value="<?= e((string)($item['category'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label for="unit">Unit</label>
            <input type="text" id="unit" name="unit" maxlength="50" required value="<?= e((string)($item['unit'] ?? '')) ?>">
            <small class="text-muted">Measurement unit only, for example tablet, capsule, box, vial, or pack. Do not enter stock quantity here.</small>
        </div>
        <div class="form-group">
            <label for="billable_item_id">Billable Item</label>
            <select id="billable_item_id" name="billable_item_id">
                <option value="">—</option>
                <?php foreach ($billableItems as $billableItem): ?>
                    <option value="<?= (int)$billableItem['id'] ?>" <?= (int)($item['billable_item_id'] ?? 0) === (int)$billableItem['id'] ? 'selected' : '' ?>>
                        <?= e((string)$billableItem['item_code']) ?> — <?= e((string)$billableItem['item_name']) ?> (<?= e(number_format((float)$billableItem['unit_price'], 2)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="is_active">Status</label>
            <select id="is_active" name="is_active">
                <option value="1" <?= (int)($item['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= (int)($item['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?= e((string)($item['description'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(storeBackToIndex()) ?>">Cancel</a>
    </div>
</form>
