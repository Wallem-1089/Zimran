<?php

declare(strict_types=1);

$radiologyRequest ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Radiology Request';
$requestSource = (string)($radiologyRequest['request_source'] ?? ($requestSource ?? 'Clinical'));
?>

<form method="post" action="<?= e($action) ?>" class="card">
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

    <div class="form-group">
        <label for="study_requested">Study Requested</label>
        <textarea id="study_requested" name="study_requested" rows="4" required><?= e((string)($radiologyRequest['study_requested'] ?? $radiologyRequest['tests_requested'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="clinical_indication">Clinical Indication</label>
        <textarea id="clinical_indication" name="clinical_indication" rows="4"><?= e((string)($radiologyRequest['clinical_indication'] ?? $radiologyRequest['clinical_information'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(radiologyBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>

