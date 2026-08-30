<?php

declare(strict_types=1);

$popRequest = $popRequest ?? [];
$visitId = (int)($visit['id'] ?? $popRequest['visit_id'] ?? 0);
$patientId = (int)($visit['patient_id'] ?? $popRequest['patient_id'] ?? 0);
$requestSource = (string)($popRequest['request_source'] ?? $requestSource ?? 'Clinical');
?>

<form method="post" action="save.php" class="card">
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= $visitId ?>">
    <input type="hidden" name="patient_id" value="<?= $patientId ?>">
    <input type="hidden" name="request_source" value="<?= e($requestSource) ?>">

    <div class="form-grid">
        <div class="form-group">
            <label for="procedure_requested">Procedure / Cast Requested</label>
            <input id="procedure_requested" name="procedure_requested" type="text" required maxlength="255" value="<?= e((string)($popRequest['procedure_requested'] ?? 'POP / Casting')) ?>">
        </div>
        <div class="form-group">
            <label for="priority">Priority</label>
            <select id="priority" name="priority" required>
                <?php foreach (['Routine', 'Urgent'] as $priority): ?>
                    <option value="<?= e($priority) ?>" <?= (string)($popRequest['priority'] ?? 'Routine') === $priority ? 'selected' : '' ?>><?= e($priority) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="clinical_indication">Clinical Indication / Reason</label>
        <textarea id="clinical_indication" name="clinical_indication" rows="5"><?= e((string)($popRequest['clinical_indication'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary">Save POP Request</button>
        <a class="btn-secondary" href="<?= e(popBackToWorkspace($visitId)) ?>">Cancel</a>
    </div>
</form>
