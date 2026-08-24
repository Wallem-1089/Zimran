<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$dressingRecord = $dressingRecordService->getById($recordId, $currentUser);
if (!$dressingRecord) {
    http_response_code(404);
    exit('Dressing record not found.');
}

$visit = nursingRequireVisit($visitService, (int)$dressingRecord['visit_id']);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canEditNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('Dressing record editing is denied.');
}

$pageTitle = 'Edit Dressing Record';
$moduleStylesheet = '/modules/visits/assets/visits.css';
if (isset($_SESSION['old_dressing_record'])) {
    $dressingRecord = array_merge($dressingRecord, (array)$_SESSION['old_dressing_record']);
    unset($_SESSION['old_dressing_record']);
}

require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Edit Dressing Record</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . (int)$visit['id']))) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="view.php?id=<?= (int)$dressingRecord['id'] ?>">View</a>
            <a class="btn-secondary" href="<?= e(dressingBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
        </div>
    </div>

    <?php if (isset($_SESSION['validation_errors'])): ?>
        <div class="alert-danger">
            <strong>Please correct the following:</strong>
            <ul>
                <?php foreach ((array)$_SESSION['validation_errors'] as $error): ?>
                    <li><?= e((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['validation_errors']); ?>
    <?php endif; ?>

    <?php $action = 'update.php'; $buttonLabel = 'Update Dressing Record'; require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
