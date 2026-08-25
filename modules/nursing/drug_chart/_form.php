<?php

declare(strict_types=1);

$record ??= [];
$prescriptions ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Drug Chart Entry';
$selectedPrescriptionId = (int)($record['prescription_id'] ?? ($_GET['prescription'] ?? 0));
?>

<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrfField() ?>
    <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
    <input type="hidden" name="patient_id" value="<?= (int)$visit['patient_id'] ?>">
    <?php if (!empty($record['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$record['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div class="form-group">
            <label for="prescription_id">Linked Prescription</label>
            <select id="prescription_id" name="prescription_id">
                <option value="">No linked prescription / external order</option>
                <?php foreach ($prescriptions as $prescription): ?>
                    <option
                        value="<?= (int)$prescription['id'] ?>"
                        data-medication="<?= e((string)$prescription['medication_name']) ?>"
                        <?= $selectedPrescriptionId === (int)$prescription['id'] ? 'selected' : '' ?>
                    >
                        <?= e((string)$prescription['medication_name']) ?>
                        <?= trim((string)($prescription['dosage'] ?? '')) !== '' ? ' - ' . e((string)$prescription['dosage']) : '' ?>
                        <?= trim((string)($prescription['frequency'] ?? '')) !== '' ? ' / ' . e((string)$prescription['frequency']) : '' ?>
                        (<?= e((string)$prescription['status']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="medication_name">Medication</label>
            <input id="medication_name" name="medication_name" required maxlength="255" value="<?= e((string)($record['medication_name'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="scheduled_time">Scheduled / Administered Time</label>
            <input id="scheduled_time" name="scheduled_time" type="datetime-local" required value="<?= e(isset($record['scheduled_time']) ? str_replace(' ', 'T', substr((string)$record['scheduled_time'], 0, 16)) : date('Y-m-d\\TH:i')) ?>">
        </div>

        <div class="form-group">
            <label for="dose_given">Dose Given</label>
            <input id="dose_given" name="dose_given" required maxlength="100" placeholder="e.g. 1 tablet, 5 ml" value="<?= e((string)($record['dose_given'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="route">Route</label>
            <input id="route" name="route" maxlength="100" placeholder="e.g. Oral, IV, IM" value="<?= e((string)($record['route'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label for="administration_status">Status</label>
            <select id="administration_status" name="administration_status" required>
                <?php foreach (['Given', 'Missed', 'Refused', 'Held'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= (string)($record['administration_status'] ?? 'Given') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="4"><?= e((string)($record['notes'] ?? '')) ?></textarea>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= e($buttonLabel) ?></button>
        <a class="btn-secondary" href="<?= e(drugChartBackToWorkspace((int)$visit['id'])) ?>">Cancel</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const prescription = document.getElementById('prescription_id');
    const medication = document.getElementById('medication_name');
    if (!prescription || !medication) {
        return;
    }
    prescription.addEventListener('change', function () {
        const option = prescription.options[prescription.selectedIndex];
        const name = option ? option.getAttribute('data-medication') : '';
        if (name) {
            medication.value = name;
        }
    });
});
</script>
