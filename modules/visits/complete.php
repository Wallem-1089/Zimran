<?php

declare(strict_types=1);

$pageTitle = 'Complete Encounter';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/VisitService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
if ($visitId <= 0) {
    header('Location: ../patients/search.php');
    exit;
}

$visitService = new VisitService($pdo);
$permissionService = new PermissionService($pdo);
$visit = $visitService->getVisitById($visitId);
$enableWritingMode = $permissionService->canUseConsultationHandwriting($currentUser);

if (!$visit) {
    http_response_code(404);
    exit('Encounter not found.');
}

if (!$permissionService->canChangeEncounterStatus($visit, $currentUser)) {
    http_response_code(403);
    exit('You are not allowed to complete this encounter.');
}

if (in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true)) {
    $_SESSION['error_message'] = 'This encounter is already closed.';
    header('Location: workspace.php?id=' . $visitId);
    exit;
}

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';
?>

<div class="main-container">
<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">
    <div class="page-header">
        <div>
            <h1>Complete Encounter</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('#' . $visitId))) ?> | <?= e(trim((string)($visit['first_name'] ?? '') . ' ' . (string)($visit['last_name'] ?? ''))) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="workspace.php?id=<?= (int)$visitId ?>">Back to Workspace</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['validation_errors'])): ?>
        <div class="alert-danger">
            <?= e(implode(' ', (array)$_SESSION['validation_errors'])) ?>
        </div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>

    <div class="card">
        <h2>Discharge / Completion Review</h2>
        <p class="text-muted">Complete this encounter only after the necessary clinical and operational work is finished.</p>

        <form method="post" action="complete_save.php" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
            <?= csrfField() ?>
            <input type="hidden" name="visit_id" value="<?= (int)$visitId ?>">

            <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Encounter Completion Entry Mode'); ?>
            <?php hmsRenderHandwritingTextarea('discharge_diagnosis', 'Final / Discharge Diagnosis', (string)($_SESSION['old_input']['discharge_diagnosis'] ?? $visit['discharge_diagnosis'] ?? ''), 4, true, $enableWritingMode); ?>
            <?php hmsRenderHandwritingTextarea('discharge_notes', 'Discharge Notes', (string)($_SESSION['old_input']['discharge_notes'] ?? $visit['discharge_notes'] ?? ''), 5, false, $enableWritingMode); ?>
            <?php hmsRenderHandwritingTextarea('follow_up_instructions', 'Follow-up Instructions', (string)($_SESSION['old_input']['follow_up_instructions'] ?? $visit['follow_up_instructions'] ?? ''), 4, false, $enableWritingMode); ?>

            <?php unset($_SESSION['old_input']); ?>

            <div class="form-actions">
                <button class="btn-primary" type="submit">Confirm Complete Encounter</button>
                <a class="btn-secondary" href="workspace.php?id=<?= (int)$visitId ?>">Cancel</a>
            </div>
        </form>
        <?php hmsRenderHandwritingScript($enableWritingMode); ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
</div>
