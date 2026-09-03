<?php
declare(strict_types=1);

$documentErrors = $_SESSION['validation_errors'] ?? [];
unset($_SESSION['validation_errors']);
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>
<?php if ($documentErrors !== []): ?><div class="alert-danger"><ul><?php foreach ((array)$documentErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="<?= e($documentAction) ?>" enctype="multipart/form-data" class="card" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <input type="hidden" name="visit_id" value="<?= (int)($visitId ?? 0) ?>">
    <?php if (!empty($document['id'])): ?>
        <input type="hidden" name="document_id" value="<?= (int)$document['id'] ?>">
        <input type="hidden" name="version" value="<?= (int)$document['version'] ?>">
    <?php endif; ?>
    <div class="form-grid">
        <?php if (empty($document['id'])): ?>
        <label>Document type
            <select name="document_type" required>
                <?php foreach ($medicalDocumentService->getAllowedDocumentTypes() as $type): ?>
                    <option value="<?= e($type) ?>"><?= e(documentTypeLabel($type)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Title <input name="title" required maxlength="200"></label>
        <label>Confidentiality
            <select name="confidentiality_level" required>
                <?php foreach ($medicalDocumentService->getAllowedConfidentialityLevels() as $level): ?>
                    <option value="<?= e($level) ?>"><?= e($level) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>
        <label>File
            <input type="file" name="document_file" required accept=".pdf,.jpg,.jpeg,.png,.txt,application/pdf,image/jpeg,image/png,text/plain">
        </label>
    </div>
    <?php if (empty($document['id'])): ?>
        <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Document Description Entry Mode'); ?>
        <?php hmsRenderHandwritingTextarea('description', 'Description', '', 4, false, $enableWritingMode, 10000); ?>
    <?php else: ?>
        <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Replacement Reason Entry Mode'); ?>
        <?php hmsRenderHandwritingTextarea('replacement_reason', 'Replacement reason', '', 3, true, $enableWritingMode, 1000); ?>
    <?php endif; ?>
    <p>Maximum size: <?= e(documentFormatBytes($medicalDocumentService->getMaximumUploadBytes())) ?>. Allowed: PDF, JPEG, PNG, and plain text.</p>
    <button class="btn-primary" type="submit"><?= e($submitLabel) ?></button>
    <a class="btn-secondary" href="<?= !empty($document['id']) ? 'view.php?id=' . (int)$document['id'] : '../chart.php?patient=' . (int)$patient['id'] . '&tab=documents' . e(documentContextQuery($visitId ?? null)) ?>">Cancel</a>
</form>
<?php hmsRenderHandwritingScript($enableWritingMode); ?>
