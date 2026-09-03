<?php
declare(strict_types=1);

$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>
<form method="post" action="<?= e($noteAction) ?>" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="visit_id" value="<?= (int)($visitId ?? 0) ?>">
    <?php if (!empty($note)): ?><input type="hidden" name="id" value="<?= (int)$note['id'] ?>"><input type="hidden" name="version" value="<?= (int)$note['version'] ?>"><?php endif; ?>
    <div class="form-grid">
        <div class="form-group"><label for="note_type">Note type</label><select id="note_type" name="note_type" required><?php foreach ($clinicalNoteService->getAllowedNoteTypes() as $type): ?><option value="<?= e($type) ?>" <?= (($note['note_type'] ?? '') === $type) ? 'selected' : '' ?>><?= e(noteTypeLabel($type)) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="confidentiality_level">Confidentiality</label><select id="confidentiality_level" name="confidentiality_level" required><?php foreach ($clinicalNoteService->getAllowedConfidentialityLevels() as $level): ?><option value="<?= e($level) ?>" <?= (($note['confidentiality_level'] ?? 'Standard') === $level) ? 'selected' : '' ?>><?= e($level) ?></option><?php endforeach; ?></select></div>
        <div class="form-group form-group-wide"><label for="title">Title</label><input id="title" name="title" maxlength="200" required value="<?= e($note['title'] ?? '') ?>"></div>
        <div class="form-group form-group-wide">
            <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Clinical Note Entry Mode'); ?>
            <?php hmsRenderHandwritingTextarea('content', 'Clinical Note content', (string)($note['content'] ?? ''), 18, true, $enableWritingMode); ?>
            <small>HTML and executable markup are not accepted. Handwriting is stored as safe stroke data and rendered read-only.</small>
        </div>
    </div>
    <button class="btn-primary" type="submit"><?= e($noteSubmitLabel) ?></button>
</form>
<?php hmsRenderHandwritingScript($enableWritingMode); ?>
