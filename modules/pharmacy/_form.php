<?php

declare(strict_types=1);

if (!isset($pharmacyPrescription, $inventoryItems, $requestSource, $formAction, $buttonLabel)) {
    return;
}

$selectedInventoryItemIds = $pharmacyPrescription['inventory_item_ids']
    ?? ($pharmacyPrescription['inventory_item_id'] ?? null);
$requestSourceNote ??= 'Clinical prescriptions are linked to this encounter without transferring ownership.';
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
        <label>Prescription Source</label>
        <div class="readonly-field"><?= e($requestSource) ?></div>
        <p class="text-muted"><?= e($requestSourceNote) ?></p>
    </div>

    <?php if ($requestSource === 'Clinical'): ?>
        <?php hmsRenderBillableItemSelect($billableItemOptions ?? [], $pharmacyPrescription['suggested_billable_item_ids'] ?? ($pharmacyPrescription['suggested_billable_item_id'] ?? null)); ?>
    <?php endif; ?>

    <?php hmsRenderInventoryItemSelect($inventoryItems, $selectedInventoryItemIds); ?>

    <div class="form-group">
        <label for="medication_name">Medication Name Snapshot</label>
        <input
            type="text"
            id="medication_name"
            name="medication_name"
            value="<?= e((string)($pharmacyPrescription['medication_name'] ?? '')) ?>">
        <small class="form-help">Required only when no inventory item is selected.</small>
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
            min="1"
            step="1"
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
