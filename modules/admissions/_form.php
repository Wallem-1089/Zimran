<?php

declare(strict_types=1);

$admission = $admission ?? [];
$wards = $wards ?? [];
$beds = $beds ?? [];
$action = $action ?? 'save.php';
$buttonLabel = $buttonLabel ?? 'Admit Patient';
$admissionConfiguredFields ??= [];
$admissionConfiguredValues ??= [];
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>

<form method="post" action="<?= e($action) ?>" class="card form-card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)($visit['id'] ?? $admission['visit_id'] ?? 0) ?>">
    <input type="hidden" name="patient_id" value="<?= (int)($visit['patient_id'] ?? $admission['patient_id'] ?? 0) ?>">

    <div class="form-grid">
        <div class="form-group">
            <label for="ward_id">Ward</label>
            <select id="ward_id" name="ward_id" required>
                <option value="">Select ward</option>
                <?php foreach ($wards as $ward): ?>
                    <option value="<?= (int)$ward['id'] ?>" <?= (int)($admission['ward_id'] ?? 0) === (int)$ward['id'] ? 'selected' : '' ?>>
                        <?= e((string)$ward['ward_name']) ?><?= isset($ward['available_beds']) ? ' — ' . (int)$ward['available_beds'] . ' available' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="bed_id">Bed</label>
            <select id="bed_id" name="bed_id" required>
                <option value="">Select available bed</option>
                <?php foreach ($beds as $bed): ?>
                    <option value="<?= (int)$bed['id'] ?>" <?= (int)($admission['bed_id'] ?? 0) === (int)$bed['id'] ? 'selected' : '' ?>>
                        <?= e((string)($bed['ward_name'] ?? 'Ward')) ?> — <?= e((string)$bed['bed_label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="admission_type">Admission Type</label>
            <select id="admission_type" name="admission_type">
                <?php foreach (['Emergency','Elective','Transfer','Observation'] as $type): ?>
                    <option value="<?= e($type) ?>" <?= ($admission['admission_type'] ?? 'Emergency') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="admitted_at">Admission Date/Time</label>
            <input type="datetime-local" id="admitted_at" name="admitted_at" value="<?= e((string)($admission['admitted_at_input'] ?? '')) ?>">
        </div>
    </div>

    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Admission Entry Mode'); ?>
    <?php hmsRenderHandwritingTextarea('admission_diagnosis', 'Admission Diagnosis', (string)($admission['admission_diagnosis'] ?? ''), 4, false, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('admission_notes', 'Admission Notes', (string)($admission['admission_notes'] ?? ''), 5, false, $enableWritingMode); ?>
    <?php hmsRenderConfiguredFields($admissionConfiguredFields, $admissionConfiguredValues); ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(admissionBackToWorkspace((int)($visit['id'] ?? $admission['visit_id'] ?? 0))) ?>">Cancel</a>
    </div>
</form>
<?php hmsRenderHandwritingScript($enableWritingMode); ?>
