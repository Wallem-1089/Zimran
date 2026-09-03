<?php

declare(strict_types=1);

$record ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save DM Sheet Entry';
$mealStatuses = $diabetesMonitoringService->getMealStatuses();
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>

<form method="post" action="<?= e($action) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$visit['patient_id'] ?>">
    <?php if (!empty($record['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$record['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="recorded_at">Recorded At</label>
            <input id="recorded_at" name="recorded_at" type="datetime-local" required value="<?= e(isset($record['recorded_at']) ? str_replace(' ', 'T', substr((string)$record['recorded_at'], 0, 16)) : date('Y-m-d\\TH:i')) ?>">
        </div>

        <div class="form-group">
            <label for="blood_glucose">Blood Glucose</label>
            <input id="blood_glucose" name="blood_glucose" type="number" step="0.01" min="0.01" max="1000" required value="<?= e((string)($record['blood_glucose'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="meal_status">Meal Status</label>
            <select id="meal_status" name="meal_status" required>
                <?php foreach ($mealStatuses as $status): ?>
                    <option value="<?= e($status) ?>" <?= (string)($record['meal_status'] ?? 'Not Recorded') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="insulin_given">Insulin Given</label>
            <input id="insulin_given" name="insulin_given" maxlength="255" placeholder="e.g. 6 units soluble insulin" value="<?= e((string)($record['insulin_given'] ?? '')) ?>">
        </div>

        <?php hmsRenderHandwritingToolbar($enableWritingMode, 'DM Sheet Entry Mode'); ?>
        <?php hmsRenderHandwritingTextarea('symptoms', 'Symptoms', (string)($record['symptoms'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('notes', 'Notes', (string)($record['notes'] ?? ''), 4, false, $enableWritingMode); ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(dmSheetBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>
<?php hmsRenderHandwritingScript($enableWritingMode); ?>
