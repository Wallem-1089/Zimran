<?php

declare(strict_types=1);

$entry = $entry ?? [];
?>
<form method="post" action="<?= e($historyAction) ?>" class="card">
    <?= csrfField() ?>
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="id" value="<?= (int)($entry['id'] ?? 0) ?>">
    <input type="hidden" name="version" value="<?= (int)($entry['version'] ?? 0) ?>">
    <input type="hidden" name="visit_id" value="<?= (int)($visitId ?? 0) ?>">
    <div class="form-grid">
        <label>History type <select name="history_type"><?php foreach ($problemListService->getAllowedHistoryTypes() as $value): ?><option <?= ($entry['history_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach; ?></select></label>
        <label>Title <input required maxlength="200" name="title" value="<?= e($entry['title'] ?? '') ?>"></label>
        <label>Event date <input type="date" name="event_date" value="<?= e($entry['event_date'] ?? '') ?>"></label>
        <label>Date precision <select name="date_precision"><?php foreach (['Exact','Month','Year','Unknown'] as $value): ?><option <?= ($entry['date_precision'] ?? 'Unknown') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach; ?></select></label>
        <label>Status <select name="status"><?php foreach (['Active','Historical'] as $value): ?><option <?= ($entry['status'] ?? 'Historical') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach; ?></select></label>
        <label>Source <input maxlength="255" name="source" value="<?= e($entry['source'] ?? '') ?>"></label>
        <label>Confidentiality <select name="confidentiality_level"><?php foreach ($problemListService->getAllowedConfidentialityLevels() as $value): ?><option <?= ($entry['confidentiality_level'] ?? 'Standard') === $value ? 'selected' : '' ?>><?= e($value) ?></option><?php endforeach; ?></select></label>
    </div>
    <label>Description <textarea required name="description" maxlength="10000"><?= e($entry['description'] ?? '') ?></textarea></label>
    <label>Clinical reason <textarea required name="reason" maxlength="1000"></textarea></label>
    <button class="btn-primary" type="submit"><?= e($historySubmitLabel) ?></button>
    <a class="btn-secondary" href="../chart.php?patient=<?= (int)$patient['id'] ?>&tab=medical_history<?= e(longitudinalQuery($visitId ?? null)) ?>">Cancel</a>
</form>
