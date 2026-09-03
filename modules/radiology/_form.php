<?php

declare(strict_types=1);

$radiologyRequest ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Radiology Request';
$requestSource = (string)($radiologyRequest['request_source'] ?? ($requestSource ?? 'Clinical'));
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>

<form method="post" action="<?= e($action) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">

    <div class="form-group">
        <label for="request_source">Request Source</label>
        <select id="request_source" name="request_source" required>
            <option value="Clinical" <?= $requestSource === 'Clinical' ? 'selected' : '' ?>>Clinical</option>
            <option value="Direct" <?= $requestSource === 'Direct' ? 'selected' : '' ?>>Direct</option>
        </select>
    </div>

    <div class="form-group">
        <label for="priority">Priority</label>
        <select id="priority" name="priority" required>
            <option value="Routine" <?= (string)($radiologyRequest['priority'] ?? 'Routine') === 'Routine' ? 'selected' : '' ?>>Routine</option>
            <option value="Urgent" <?= (string)($radiologyRequest['priority'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
        </select>
    </div>

    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Radiology Request Entry Mode'); ?>
    <?php hmsRenderHandwritingTextarea('study_requested', 'Study Requested', (string)($radiologyRequest['study_requested'] ?? $radiologyRequest['tests_requested'] ?? ''), 4, true, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('clinical_indication', 'Clinical Indication', (string)($radiologyRequest['clinical_indication'] ?? $radiologyRequest['clinical_information'] ?? ''), 4, false, $enableWritingMode); ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(radiologyBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>

<?php hmsRenderHandwritingScript($enableWritingMode); ?>
