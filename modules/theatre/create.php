<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$visit = theatreRequireVisit($visitService, $visitId);

if (!$theatreTablesReady) {
    http_response_code(503);
    exit('Theatre tables are not available yet. Apply Migration 029 to enable this section.');
}

if (!$permissionService->canCreateTheatre($visit, $currentUser)) {
    http_response_code(403);
    exit('Theatre creation is denied.');
}

if ($theatreService->getByVisit($visitId, $currentUser)) {
    header('Location: view.php?visit=' . $visitId);
    exit;
}

$theatre = $_SESSION['old_theatre'] ?? ['visit_id' => $visitId];
unset($_SESSION['old_theatre']);
$theatreConfiguredFields = $configurableFormService->listFields('theatre_record', true);
$theatreConfiguredValues = $_SESSION['old_configured_fields'] ?? [];
unset($_SESSION['old_configured_fields']);

$latestVitalSigns = $vitalSignsService
    ? $vitalSignsService->getLatestByVisit($visitId, $currentUser)
    : null;

$pageTitle = 'Start Theatre Record';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
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
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert-danger"><?= nl2br(e((string)$_SESSION['error_message'])) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <div class="page-header">
        <div>
            <h1>Start Theatre Record</h1>
            <p><?= e($visit['visit_number'] ?? ('Encounter #' . $visitId)) ?></p>
        </div>
        <div>
            <a class="btn-secondary" href="<?= e(theatreBackToWorkspace((int)$visit['id'])) ?>">Workspace</a>
        </div>
    </div>
    <?php if ($latestVitalSigns !== null): ?>
        <div class="card">
            <h3>Latest Vital Signs</h3>
            <?php $latest = $latestVitalSigns; require __DIR__ . '/../vital_signs/partials/record_card.php'; ?>
        </div>
    <?php endif; ?>
    <?php $action = 'save.php'; $buttonLabel = 'Save Draft'; require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
