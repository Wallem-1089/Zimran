<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$visit = nursingRequireVisit($visitService, $visitId);
nursingRequireAccess($permissionService, $visit, $currentUser);

if (!$permissionService->canCreateNursing($visit, $currentUser)) {
    http_response_code(403);
    exit('Dressing record creation is denied.');
}

$pageTitle = 'New Dressing Record';
$moduleStylesheet = '/modules/visits/assets/visits.css';
$dressingRecord = $_SESSION['old_dressing_record'] ?? [
    'visit_id' => $visitId,
    'patient_id' => (int)$visit['patient_id'],
];
unset($_SESSION['old_dressing_record']);
$dressingConfiguredFields = $configurableFormService->listFields('dressing_record', true);
$dressingConfiguredValues = $_SESSION['old_configured_fields'] ?? [];
unset($_SESSION['old_configured_fields']);

require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>New Dressing Record</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . $visitId))) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="<?= e(dressingBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
            <a class="btn-secondary" href="<?= e(dressingBackToChart((int)$visit['patient_id'])) ?>">Patient Chart</a>
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

    <?php $action = 'save.php'; $buttonLabel = 'Save Dressing Record'; require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
