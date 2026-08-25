<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$record = $diabetesMonitoringService->getById($recordId, $currentUser);
if (!$record) {
    http_response_code(404);
    exit('DM Sheet entry not found.');
}

$visit = nursingRequireVisit($visitService, (int)$record['visit_id']);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canEditNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('DM Sheet entry edit is denied.');
}

$old = $_SESSION['old_dm_sheet'] ?? null;
unset($_SESSION['old_dm_sheet']);
if (is_array($old)) {
    $record = array_merge($record, $old);
}
$action = 'update.php';
$buttonLabel = 'Update DM Sheet Entry';

$pageTitle = 'Edit DM Sheet Entry';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Edit DM Sheet Entry</h1>
            <p><?= e((string)$record['recorded_at']) ?></p>
        </div>
        <a class="btn-secondary" href="view.php?id=<?= (int)$record['id'] ?>">Cancel</a>
    </div>

    <?php require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
