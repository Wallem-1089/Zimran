<?php

declare(strict_types=1);

$laboratoryRequest ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Laboratory Request';
$requestSource = (string)($laboratoryRequest['request_source'] ?? ($requestSource ?? 'Clinical'));
$requestSourceNote ??= $requestSource === 'Direct'
    ? 'Direct Laboratory is for patients whose active encounter is currently in Laboratory.'
    : 'Clinical requests are linked to this encounter without transferring ownership.';
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>

<form method="post" action="<?= e($action) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="request_source" value="<?= e($requestSource) ?>">

    <div class="form-group">
        <label>Request Source</label>
        <div class="readonly-field"><?= e($requestSource) ?></div>
        <p class="text-muted"><?= e($requestSourceNote) ?></p>
    </div>

    <div class="form-group">
        <label for="priority">Priority</label>
        <select id="priority" name="priority" required>
            <option value="Routine" <?= (string)($laboratoryRequest['priority'] ?? 'Routine') === 'Routine' ? 'selected' : '' ?>>Routine</option>
            <option value="Urgent" <?= (string)($laboratoryRequest['priority'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
        </select>
    </div>

    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Laboratory Request Entry Mode'); ?>
    <?php hmsRenderHandwritingTextarea('tests_requested', 'Tests Requested', (string)($laboratoryRequest['tests_requested'] ?? ''), 4, true, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('clinical_information', 'Clinical Information', (string)($laboratoryRequest['clinical_information'] ?? ''), 4, false, $enableWritingMode); ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(laboratoryBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>

<?php hmsRenderHandwritingScript($enableWritingMode); ?>
