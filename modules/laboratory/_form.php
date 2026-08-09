<?php

declare(strict_types=1);

$laboratoryRequest ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Laboratory Request';
$requestSource = (string)($laboratoryRequest['request_source'] ?? ($requestSource ?? 'Clinical'));
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
            <option value="Routine" <?= (string)($laboratoryRequest['priority'] ?? 'Routine') === 'Routine' ? 'selected' : '' ?>>Routine</option>
            <option value="Urgent" <?= (string)($laboratoryRequest['priority'] ?? '') === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
        </select>
    </div>

    <div class="form-group">
        <label for="tests_requested">Tests Requested</label>
        <textarea id="tests_requested" name="tests_requested" rows="4" required><?= e((string)($laboratoryRequest['tests_requested'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="clinical_information">Clinical Information</label>
        <textarea id="clinical_information" name="clinical_information" rows="4"><?= e((string)($laboratoryRequest['clinical_information'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(laboratoryBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>
