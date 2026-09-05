<?php

declare(strict_types=1);

$ecgRequest = $ecgRequest ?? [];
$requestSource = ecgRequestSourceLabel((string)($ecgRequest['request_source'] ?? $requestSource ?? 'Clinical'));
$requestSourceNote ??= $requestSource === 'Direct'
    ? 'Direct ECG is for patients whose active encounter is currently in ECG.'
    : 'Clinical requests are linked to this encounter without transferring ownership.';
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>

<form method="post" action="save.php" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$visit['patient_id'] ?>">
    <input type="hidden" name="request_source" value="<?= e($requestSource) ?>">

    <div class="form-group">
        <label>Request Source</label>
        <div class="readonly-field"><?= e($requestSource) ?></div>
        <p class="text-muted"><?= e($requestSourceNote) ?></p>
    </div>

    <div class="form-group">
        <label for="study_requested">Study Requested</label>
        <input id="study_requested" name="study_requested" type="text" required value="<?= e((string)($ecgRequest['study_requested'] ?? 'ECG')) ?>">
    </div>

    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'ECG Request Entry Mode'); ?>
    <?php hmsRenderHandwritingTextarea('clinical_indication', 'Clinical Indication / Reason', (string)($ecgRequest['clinical_indication'] ?? ''), 5, false, $enableWritingMode); ?>

    <div class="form-group">
        <label for="priority">Priority</label>
        <select id="priority" name="priority">
            <?php foreach (['Routine', 'Urgent'] as $priority): ?>
                <option value="<?= e($priority) ?>" <?= (string)($ecgRequest['priority'] ?? 'Routine') === $priority ? 'selected' : '' ?>>
                    <?= e($priority) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary">Save ECG Request</button>
        <a class="btn-secondary" href="<?= e(ecgBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>

<?php hmsRenderHandwritingScript($enableWritingMode); ?>
