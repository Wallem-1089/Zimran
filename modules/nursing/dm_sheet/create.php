<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$visit = nursingRequireVisit($visitService, $visitId);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canCreateNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('DM Sheet entry creation is denied.');
}

$record = $_SESSION['old_dm_sheet'] ?? [];
unset($_SESSION['old_dm_sheet']);
$dmSheetConfiguredFields = $configurableFormService->listFields('dm_sheet', true);
$dmSheetConfiguredValues = $_SESSION['old_configured_fields'] ?? [];
unset($_SESSION['old_configured_fields']);

$pageTitle = 'New DM Sheet Entry';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>New DM Sheet Entry</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('#' . (int)$visit['id']))) ?></p>
        </div>
        <a class="btn-secondary" href="<?= e(dmSheetBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
    </div>

    <?php require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
