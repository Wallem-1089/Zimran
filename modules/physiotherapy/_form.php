<?php

declare(strict_types=1);

$physiotherapyRecord ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Physiotherapy Record';
$recordSource = (string)($physiotherapyRecord['record_source'] ?? ($recordSource ?? 'Clinical'));
?>

<form method="post" action="<?= e($action) ?>" class="card">
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

    <div class="form-group">
        <label for="referral_reason">Referral Reason</label>
        <textarea id="referral_reason" name="referral_reason" rows="3"><?= e((string)($physiotherapyRecord['referral_reason'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="presenting_problem">Presenting Problem</label>
        <textarea id="presenting_problem" name="presenting_problem" rows="4" required><?= e((string)($physiotherapyRecord['presenting_problem'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="assessment">Assessment</label>
        <textarea id="assessment" name="assessment" rows="5" required><?= e((string)($physiotherapyRecord['assessment'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="functional_limitations">Functional Limitations</label>
        <textarea id="functional_limitations" name="functional_limitations" rows="4"><?= e((string)($physiotherapyRecord['functional_limitations'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="treatment_plan">Treatment Plan</label>
        <textarea id="treatment_plan" name="treatment_plan" rows="5" required><?= e((string)($physiotherapyRecord['treatment_plan'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="goals">Goals</label>
        <textarea id="goals" name="goals" rows="4"><?= e((string)($physiotherapyRecord['goals'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="precautions">Precautions</label>
        <textarea id="precautions" name="precautions" rows="4"><?= e((string)($physiotherapyRecord['precautions'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(physiotherapyBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>
