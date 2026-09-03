<?php

declare(strict_types=1);

$alert = $alert ?? [];
$alertTypes = $clinicalSafetyService->getAllowedAlertTypes();
$priorities = $clinicalSafetyService->getAllowedAlertPriorities();
$confidentialityLevels = $clinicalSafetyService->getAllowedConfidentialityLevels();
$visitId = $visitId ?? null;
$contextQuery = clinicalSafetyQuery($visitId);
if (!($canViewConfidential ?? false)) {
    $confidentialityLevels = ['Standard'];
}
$enableWritingMode ??= isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);
?>
<form class="card" method="post" action="<?= e($alertAction) ?>" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
    <?php if ($visitId !== null): ?>
        <input type="hidden" name="visit_id" value="<?= (int)$visitId ?>">
        <input type="hidden" name="event_visit_id" value="<?= (int)$visitId ?>">
    <?php endif; ?>
    <?php if (!empty($alert['id'])): ?><input type="hidden" name="id" value="<?= (int)$alert['id'] ?>"><input type="hidden" name="version" value="<?= (int)$alert['version'] ?>"><?php endif; ?>
    <div class="chart-detail-grid">
        <div><label for="alert_type">Alert Type</label><select id="alert_type" name="alert_type" required><?php foreach ($alertTypes as $type): ?><option value="<?= e($type) ?>" <?= ($alert['alert_type'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></div>
        <div><label for="title">Title</label><input id="title" name="title" maxlength="150" required value="<?= e($alert['title'] ?? '') ?>"></div>
        <div><label for="priority">Priority</label><select id="priority" name="priority" required><?php foreach ($priorities as $priority): ?><option value="<?= e($priority) ?>" <?= ($alert['priority'] ?? 'Medium') === $priority ? 'selected' : '' ?>><?= e($priority) ?></option><?php endforeach; ?></select></div>
        <div><label for="confidentiality_level">Confidentiality</label><select id="confidentiality_level" name="confidentiality_level" required><?php foreach ($confidentialityLevels as $level): ?><option value="<?= e($level) ?>" <?= ($alert['confidentiality_level'] ?? 'Standard') === $level ? 'selected' : '' ?>><?= e($level) ?></option><?php endforeach; ?></select></div>
        <div><label for="starts_at">Starts</label><input id="starts_at" type="datetime-local" name="starts_at" value="<?= e(isset($alert['starts_at']) ? str_replace(' ', 'T', substr((string)$alert['starts_at'], 0, 16)) : '') ?>"></div>
        <div><label for="expires_at">Expires</label><input id="expires_at" type="datetime-local" name="expires_at" value="<?= e(isset($alert['expires_at']) ? str_replace(' ', 'T', substr((string)$alert['expires_at'], 0, 16)) : '') ?>"></div>
    </div>
    <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Clinical Alert Entry Mode'); ?>
    <?php hmsRenderHandwritingTextarea('reason', 'Clinical reason', (string)($alert['reason'] ?? ''), 4, true, $enableWritingMode); ?>
    <?php hmsRenderHandwritingTextarea('change_reason', 'Change reason', empty($alert['id']) ? 'Initial clinical safety alert.' : '', 3, true, $enableWritingMode); ?>
    <button class="btn-primary" type="submit"><?= e($alertSubmitLabel) ?></button>
    <a class="btn-secondary" href="index.php?patient=<?= (int)$patient['id'] ?><?= e($contextQuery) ?>#alerts">Cancel</a>
</form>
<?php hmsRenderHandwritingScript($enableWritingMode); ?>
