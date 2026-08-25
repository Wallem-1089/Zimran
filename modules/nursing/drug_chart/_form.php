<?php

declare(strict_types=1);

$record ??= [];
$prescriptions ??= [];
$action ??= 'save.php';
$buttonLabel ??= 'Save Drug Chart Entry';
$selectedPrescriptionId = (int)($record['prescription_id'] ?? ($_GET['prescription'] ?? 0));
$selectedPrescription = null;
foreach ($prescriptions as $prescriptionOption) {
    if ((int)$prescriptionOption['id'] === $selectedPrescriptionId) {
        $selectedPrescription = $prescriptionOption;
        break;
    }
}
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
                        data-dosage="<?= e((string)($prescription['dosage'] ?? '')) ?>"
                        data-frequency="<?= e((string)($prescription['frequency'] ?? '')) ?>"
                        data-duration="<?= e((string)($prescription['duration'] ?? '')) ?>"
                        data-instructions="<?= e((string)($prescription['instructions'] ?? '')) ?>"
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

        <div class="form-group full-width">
            <div id="linked_prescription_preview" class="alert-info" style="<?= $selectedPrescription ? '' : 'display:none;' ?>">
                <strong>Linked prescription context</strong>
                <div class="summary-grid compact-summary">
                    <div class="summary-item"><span class="summary-label">Medication</span> <span class="summary-value" data-preview="medication"><?= e((string)($selectedPrescription['medication_name'] ?? '')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Dosage</span> <span class="summary-value" data-preview="dosage"><?= e((string)($selectedPrescription['dosage'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Frequency</span> <span class="summary-value" data-preview="frequency"><?= e((string)($selectedPrescription['frequency'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Duration</span> <span class="summary-value" data-preview="duration"><?= e((string)($selectedPrescription['duration'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value" data-preview="status"><?= e((string)($selectedPrescription['status'] ?? '-')) ?></span></div>
                </div>
                <p class="text-muted" data-preview="instructions">
                    <?= trim((string)($selectedPrescription['instructions'] ?? '')) !== '' ? e((string)$selectedPrescription['instructions']) : 'No prescription instructions recorded.' ?>
                </p>
            </div>
            <small class="text-muted">Select a Pharmacy prescription to link this administration entry. You can still type a manual/external medication when no prescription is selected.</small>
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
    const dose = document.getElementById('dose_given');
    const notes = document.getElementById('notes');
    const preview = document.getElementById('linked_prescription_preview');
    if (!prescription || !medication) {
        return;
    }
    function updateFromPrescription() {
        const option = prescription.options[prescription.selectedIndex];
        const name = option ? option.getAttribute('data-medication') || '' : '';
        const dosage = option ? option.getAttribute('data-dosage') || '' : '';
        const frequency = option ? option.getAttribute('data-frequency') || '' : '';
        const duration = option ? option.getAttribute('data-duration') || '' : '';
        const instructions = option ? option.getAttribute('data-instructions') || '' : '';
        if (name) {
            medication.value = name;
        }
        if (dosage && dose && dose.value.trim() === '') {
            dose.value = dosage;
        }
        if (instructions && notes && notes.value.trim() === '') {
            notes.value = 'Prescription instructions: ' + instructions;
        }
        if (preview) {
            preview.style.display = name ? '' : 'none';
            preview.querySelector('[data-preview="medication"]').textContent = name || '-';
            preview.querySelector('[data-preview="dosage"]').textContent = dosage || '-';
            preview.querySelector('[data-preview="frequency"]').textContent = frequency || '-';
            preview.querySelector('[data-preview="duration"]').textContent = duration || '-';
            const statusMatch = option && option.value ? option.textContent.match(/\(([^)]+)\)\s*$/) : null;
            preview.querySelector('[data-preview="status"]').textContent = statusMatch ? statusMatch[1] : '-';
            preview.querySelector('[data-preview="instructions"]').textContent = instructions || 'No prescription instructions recorded.';
        }
    }
    prescription.addEventListener('change', updateFromPrescription);
    if (prescription.value) {
        updateFromPrescription();
    }
});
</script>
