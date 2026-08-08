<?php

declare(strict_types=1);

$allergy = $allergy ?? [];
$allergyTypes = $clinicalSafetyService->getAllowedAllergyTypes();
$severityValues = $clinicalSafetyService->getAllowedSeverityValues();
$visitId = $visitId ?? null;
$contextQuery = clinicalSafetyQuery($visitId);
?>
<form class="card" method="post" action="<?= e($allergyAction) ?>">
    <?= csrfField() ?>
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <?php if ($visitId !== null): ?>
        <input type="hidden" name="visit_id" value="<?= (int)$visitId ?>">
        <input type="hidden" name="source_visit_id" value="<?= (int)$visitId ?>">
    <?php endif; ?>
    <?php if (!empty($allergy['id'])): ?>
        <input type="hidden" name="id" value="<?= (int)$allergy['id'] ?>">
        <input type="hidden" name="version" value="<?= (int)$allergy['version'] ?>">
    <?php endif; ?>
    <div class="chart-detail-grid">
        <div><label for="allergy_type">Allergy Type</label><select id="allergy_type" name="allergy_type" required><?php foreach ($allergyTypes as $type): ?><option value="<?= e($type) ?>" <?= ($allergy['allergy_type'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></div>
        <div><label for="substance">Substance</label><input id="substance" name="substance" maxlength="150" required value="<?= e($allergy['substance'] ?? '') ?>"></div>
        <div><label for="severity">Severity</label><select id="severity" name="severity" required><?php foreach ($severityValues as $severity): ?><option value="<?= e($severity) ?>" <?= ($allergy['severity'] ?? 'Unknown') === $severity ? 'selected' : '' ?>><?= e($severity) ?></option><?php endforeach; ?></select></div>
        <div><label for="onset_date">Onset Date</label><input id="onset_date" type="date" name="onset_date" value="<?= e($allergy['onset_date'] ?? '') ?>"></div>
    </div>
    <label for="reaction">Reaction</label><textarea id="reaction" name="reaction" maxlength="500"><?= e($allergy['reaction'] ?? '') ?></textarea>
    <label for="notes">Notes</label><textarea id="notes" name="notes"><?= e($allergy['notes'] ?? '') ?></textarea>
    <label for="reason">Reason</label><textarea id="reason" name="reason" required></textarea>
    <button class="btn-primary" type="submit"><?= e($allergySubmitLabel) ?></button>
    <a class="btn-secondary" href="index.php?patient=<?= (int)$patient['id'] ?><?= e($contextQuery) ?>#allergies">Cancel</a>
</form>
