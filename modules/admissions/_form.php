<?php

declare(strict_types=1);

$admission = $admission ?? [];
$wards = $wards ?? [];
$beds = $beds ?? [];
$action = $action ?? 'save.php';
$buttonLabel = $buttonLabel ?? 'Admit Patient';
?>

<form method="post" action="<?= e($action) ?>" class="card form-card">
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)($visit['id'] ?? $admission['visit_id'] ?? 0) ?>">
    <input type="hidden" name="patient_id" value="<?= (int)($visit['patient_id'] ?? $admission['patient_id'] ?? 0) ?>">

    <div class="form-grid">
        <div class="form-group">
            <label for="ward_id">Ward</label>
            <select id="ward_id" name="ward_id" required>
                <option value="">Select ward</option>
                <?php foreach ($wards as $ward): ?>
                    <option value="<?= (int)$ward['id'] ?>" <?= (int)($admission['ward_id'] ?? 0) === (int)$ward['id'] ? 'selected' : '' ?>>
                        <?= e((string)$ward['ward_name']) ?><?= isset($ward['available_beds']) ? ' — ' . (int)$ward['available_beds'] . ' available' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="bed_id">Bed</label>
            <select id="bed_id" name="bed_id" required>
                <option value="">Select available bed</option>
                <?php foreach ($beds as $bed): ?>
                    <option value="<?= (int)$bed['id'] ?>" <?= (int)($admission['bed_id'] ?? 0) === (int)$bed['id'] ? 'selected' : '' ?>>
                        <?= e((string)($bed['ward_name'] ?? 'Ward')) ?> — <?= e((string)$bed['bed_label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="admission_type">Admission Type</label>
            <select id="admission_type" name="admission_type">
                <?php foreach (['Emergency','Elective','Transfer','Observation'] as $type): ?>
                    <option value="<?= e($type) ?>" <?= ($admission['admission_type'] ?? 'Emergency') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="admitted_at">Admission Date/Time</label>
            <input type="datetime-local" id="admitted_at" name="admitted_at" value="<?= e((string)($admission['admitted_at_input'] ?? '')) ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="admission_diagnosis">Admission Diagnosis</label>
        <textarea id="admission_diagnosis" name="admission_diagnosis" rows="4"><?= e((string)($admission['admission_diagnosis'] ?? '')) ?></textarea>
    </div>

    <div class="form-group">
        <label for="admission_notes">Admission Notes</label>
        <textarea id="admission_notes" name="admission_notes" rows="5"><?= e((string)($admission['admission_notes'] ?? '')) ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(admissionBackToWorkspace((int)($visit['id'] ?? $admission['visit_id'] ?? 0))) ?>">Cancel</a>
    </div>
</form>
