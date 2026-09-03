<?php

declare(strict_types=1);

if (!isset($pharmacyPrescription, $inventoryItems, $requestSource, $formAction, $buttonLabel)) {
    return;
}

$selectedInventoryItemId = (int)($pharmacyPrescription['inventory_item_id'] ?? 0);
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>

<form method="post" action="<?= e($formAction) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$pharmacyPrescription['visit_id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$pharmacyPrescription['patient_id'] ?>">
    <input type="hidden" name="prescription_source" value="<?= e($requestSource) ?>">
    <?php if (isset($pharmacyPrescription['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$pharmacyPrescription['id'] ?>">
    <?php endif; ?>

    <div class="form-group">
        <label for="inventory_item_id">Medication / Inventory Item</label>
        <select id="inventory_item_id" name="inventory_item_id">
            <option value="">Select an item or leave blank for free text</option>
            <?php foreach ($inventoryItems as $item): ?>
                <?php
                    $unitPrice = $item['unit_price'] ?? null;
                    $priceLabel = $unitPrice !== null ? ' — ₦' . number_format((float)$unitPrice, 2) : '';
                    $stockLabel = isset($item['pharmacy_stock_available']) ? ' — Stock: ' . number_format((float)$item['pharmacy_stock_available'], 2) : '';
                ?>
                <option value="<?= (int)$item['id'] ?>"<?= (int)$item['id'] === $selectedInventoryItemId ? ' selected' : '' ?>>
                    <?= e((string)$item['item_name']) ?><?= e($priceLabel . $stockLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="medication_name">Medication Name Snapshot</label>
        <input
            type="text"
            id="medication_name"
            name="medication_name"
            value="<?= e((string)($pharmacyPrescription['medication_name'] ?? '')) ?>"
            required>
    </div>

    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Prescription Entry Mode'); ?>
    <?php hmsRenderHandwritingTextarea('dosage', 'Dosage', (string)($pharmacyPrescription['dosage'] ?? ''), 2, false, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('frequency', 'Frequency', (string)($pharmacyPrescription['frequency'] ?? ''), 2, false, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('duration', 'Duration', (string)($pharmacyPrescription['duration'] ?? ''), 2, false, $enableWritingMode); ?>

    <div class="form-group">
        <label for="quantity">Quantity</label>
        <input
            type="number"
            id="quantity"
            name="quantity"
            min="0.01"
            step="0.01"
            value="<?= e((string)($pharmacyPrescription['quantity'] ?? '')) ?>"
            required>
    </div>

    <?php hmsRenderHandwritingTextarea('instructions', 'Instructions', (string)($pharmacyPrescription['instructions'] ?? ''), 4, false, $enableWritingMode); ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(pharmacyBackToWorkspace((int)$pharmacyPrescription['visit_id'])) ?>">Workspace</a>
    </div>
</form>

<?php hmsRenderHandwritingScript($enableWritingMode); ?>
