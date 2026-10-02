<?php

declare(strict_types=1);

$physiotherapyRecord ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Physiotherapy Request';
$recordSource = (string)($physiotherapyRecord['record_source'] ?? ($recordSource ?? 'Clinical'));
$recordSourceNote ??= 'Clinical referrals are linked to this encounter without transferring ownership.';
$physiotherapyConfiguredFields ??= [];
$physiotherapyConfiguredValues ??= [];
$physiotherapyRequestOnly = !empty($physiotherapyRequestOnly);
$billableItemsLocked = !empty($billableItemsLocked);
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>

<form method="post" action="<?= e($action) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <?php if (!empty($physiotherapyRecord['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$physiotherapyRecord['id'] ?>">
    <?php endif; ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="record_source" value="<?= e($recordSource) ?>">

    <div class="form-group">
        <label>Record Source</label>
        <div class="readonly-field"><?= e($recordSource) ?></div>
        <p class="text-muted"><?= e($recordSourceNote) ?></p>
    </div>

    <?php if ($billableItemsLocked): ?>
        <div class="form-group">
            <label>Billable Item</label>
            <div class="readonly-field">Locked after Accounts clearance</div>
            <p class="text-muted">The billable item was charged when this Physiotherapy request was created. Editing this clinical record will not create another charge.</p>
        </div>
    <?php else: ?>
        <?php hmsRenderBillableItemSelect($billableItemOptions ?? [], $physiotherapyRecord['suggested_billable_item_ids'] ?? ($physiotherapyRecord['suggested_billable_item_id'] ?? null)); ?>
    <?php endif; ?>

    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Physiotherapy Entry Mode'); ?>
    <?php if ($physiotherapyRequestOnly): ?>
        <div class="alert-info">
            Create the Physiotherapy request and billing task first. Assessment, treatment plan, sessions, goals, and completion unlock after Accounts clearance.
        </div>
    <?php endif; ?>
    <?php hmsRenderHandwritingTextarea('referral_reason', 'Referral Reason', (string)($physiotherapyRecord['referral_reason'] ?? ''), 3, false, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('presenting_problem', 'Presenting Problem', (string)($physiotherapyRecord['presenting_problem'] ?? ''), 4, true, $enableWritingMode); ?>
    <?php if (!$physiotherapyRequestOnly): ?>
        <?php hmsRenderHandwritingTextarea('assessment', 'Assessment', (string)($physiotherapyRecord['assessment'] ?? ''), 5, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('functional_limitations', 'Functional Limitations', (string)($physiotherapyRecord['functional_limitations'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('treatment_plan', 'Treatment Plan', (string)($physiotherapyRecord['treatment_plan'] ?? ''), 5, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('goals', 'Goals', (string)($physiotherapyRecord['goals'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderHandwritingTextarea('precautions', 'Precautions', (string)($physiotherapyRecord['precautions'] ?? ''), 4, false, $enableWritingMode); ?>
        <?php hmsRenderConfiguredFields($physiotherapyConfiguredFields, $physiotherapyConfiguredValues); ?>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(physiotherapyBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>

<?php hmsRenderHandwritingScript($enableWritingMode); ?>
