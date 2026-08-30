<?php

declare(strict_types=1);

$ecgRequest = $ecgRequest ?? [];
$requestSource = ecgRequestSourceLabel((string)($ecgRequest['request_source'] ?? $requestSource ?? 'Clinical'));
?>

<form method="post" action="save.php" class="card">
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$visit['patient_id'] ?>">
    <input type="hidden" name="request_source" value="<?= e($requestSource) ?>">

    <div class="form-group">
        <label for="study_requested">Study Requested</label>
        <input id="study_requested" name="study_requested" type="text" required value="<?= e((string)($ecgRequest['study_requested'] ?? 'ECG')) ?>">
    </div>

    <div class="form-group">
        <label for="clinical_indication">Clinical Indication / Reason</label>
        <textarea id="clinical_indication" name="clinical_indication" rows="5"><?= e((string)($ecgRequest['clinical_indication'] ?? '')) ?></textarea>
    </div>

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

