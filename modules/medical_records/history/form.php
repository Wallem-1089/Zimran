<?php

declare(strict_types=1);

$entry = $entry ?? [];
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>
<form method="post" action="<?= e($historyAction) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
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
    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Medical History Entry Mode'); ?>
    <?php hmsRenderHandwritingTextarea('description', 'Description', (string)($entry['description'] ?? ''), 5, true, $enableWritingMode, 10000); ?>
    <?php hmsRenderHandwritingTextarea('reason', 'Clinical reason', '', 3, true, $enableWritingMode, 1000); ?>
    <button class="btn-primary" type="submit"><?= e($historySubmitLabel) ?></button>
    <a class="btn-secondary" href="../chart.php?patient=<?= (int)$patient['id'] ?>&tab=medical_history<?= e(longitudinalQuery($visitId ?? null)) ?>">Cancel</a>
</form>
<?php hmsRenderHandwritingScript($enableWritingMode); ?>
