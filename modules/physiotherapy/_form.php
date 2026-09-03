<?php

declare(strict_types=1);

$physiotherapyRecord ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Physiotherapy Record';
$recordSource = (string)($physiotherapyRecord['record_source'] ?? ($recordSource ?? 'Clinical'));
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>

<form method="post" action="<?= e($action) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">

    <div class="form-group">
        <label for="record_source">Record Source</label>
        <select id="record_source" name="record_source" required>
            <option value="Clinical" <?= $recordSource === 'Clinical' ? 'selected' : '' ?>>Clinical</option>
            <option value="Direct" <?= $recordSource === 'Direct' ? 'selected' : '' ?>>Direct</option>
        </select>
    </div>

    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Physiotherapy Entry Mode'); ?>
    <?php hmsRenderHandwritingTextarea('referral_reason', 'Referral Reason', (string)($physiotherapyRecord['referral_reason'] ?? ''), 3, false, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('presenting_problem', 'Presenting Problem', (string)($physiotherapyRecord['presenting_problem'] ?? ''), 4, true, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('assessment', 'Assessment', (string)($physiotherapyRecord['assessment'] ?? ''), 5, true, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('functional_limitations', 'Functional Limitations', (string)($physiotherapyRecord['functional_limitations'] ?? ''), 4, false, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('treatment_plan', 'Treatment Plan', (string)($physiotherapyRecord['treatment_plan'] ?? ''), 5, true, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('goals', 'Goals', (string)($physiotherapyRecord['goals'] ?? ''), 4, false, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('precautions', 'Precautions', (string)($physiotherapyRecord['precautions'] ?? ''), 4, false, $enableWritingMode); ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(physiotherapyBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>

<?php hmsRenderHandwritingScript($enableWritingMode); ?>
