<?php

declare(strict_types=1);

$pageTitle = 'Reopen Encounter';
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

if (!$permissionService->canReopenEncounter($visit, $currentUser)) {
    http_response_code(403);
    exit('You are not allowed to reopen this encounter.');
}

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';
?>

<div class="main-container">
<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">
    <div class="page-header">
        <div>
            <h1>Reopen Encounter</h1>
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
        <h2>Reopen Review</h2>
        <p class="text-muted">
            Reopening returns this completed encounter to its current department
            for further work. Financial records and existing clinical records are
            not reversed or deleted.
        </p>

        <form method="post" action="reopen_save.php" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
            <?= csrfField() ?>
            <input type="hidden" name="visit_id" value="<?= (int)$visitId ?>">

            <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Reopen Reason Entry Mode'); ?>
            <?php hmsRenderHandwritingTextarea('reopen_reason', 'Reason for Reopening', (string)($_SESSION['old_input']['reopen_reason'] ?? ''), 5, true, $enableWritingMode); ?>

            <?php unset($_SESSION['old_input']); ?>

            <div class="form-actions">
                <button class="btn-primary" type="submit">Confirm Reopen Encounter</button>
                <a class="btn-secondary" href="workspace.php?id=<?= (int)$visitId ?>">Cancel</a>
            </div>
        </form>
        <?php hmsRenderHandwritingScript($enableWritingMode); ?>
    </div>
</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
</div>
