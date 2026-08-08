<?php

declare(strict_types=1);

$problem = $problem ?? [];
?>
<form method="post" action="<?= e($problemAction) ?>" class="card">
    <?= csrfField() ?>
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="id" value="<?= (int)($problem['id'] ?? 0) ?>">
    <input type="hidden" name="version" value="<?= (int)($problem['version'] ?? 0) ?>">
    <input type="hidden" name="visit_id" value="<?= (int)($visitId ?? 0) ?>">
    <div class="form-grid">
        <label>Problem name <input required maxlength="200" name="problem_name" value="<?= e($problem['problem_name'] ?? '') ?>"></label>
        <label>Category <select name="category"><?php foreach ($problemListService->getAllowedProblemCategories() as $value): ?><option <?= ($problem['category'] ?? '') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach; ?></select></label>
        <label>Severity <select name="severity"><?php foreach ($problemListService->getAllowedProblemSeverities() as $value): ?><option <?= ($problem['severity'] ?? 'Unknown') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach; ?></select></label>
        <label>Onset date <input type="date" name="onset_date" value="<?= e($problem['onset_date'] ?? '') ?>"></label>
        <label>Code system <input maxlength="60" name="problem_code_system" value="<?= e($problem['problem_code_system'] ?? '') ?>"></label>
        <label>Code <input maxlength="80" name="problem_code" value="<?= e($problem['problem_code'] ?? '') ?>"></label>
        <label>Confidentiality <select name="confidentiality_level"><?php foreach ($problemListService->getAllowedConfidentialityLevels() as $value): ?><option <?= ($problem['confidentiality_level'] ?? 'Standard') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach; ?></select></label>
    </div>
    <label>Notes <textarea name="notes" maxlength="10000"><?= e($problem['notes'] ?? '') ?></textarea></label>
    <label>Clinical reason <textarea required name="reason" maxlength="1000"></textarea></label>
    <button class="btn-primary" type="submit"><?= e($problemSubmitLabel) ?></button>
    <a class="btn-secondary" href="../chart.php?patient=<?= (int)$patient['id'] ?>&tab=problems<?= e(longitudinalQuery($visitId ?? null)) ?>">Cancel</a>
</form>
