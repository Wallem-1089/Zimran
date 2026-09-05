<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$recordId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$theatre = $theatreService->getById($recordId, $currentUser);

if (!$theatre) {
    http_response_code(404);
    exit('Theatre record not found.');
}

$visit = theatreRequireVisit($visitService, (int)$theatre['visit_id']);
theatreRequireAccess($permissionService, $visit, $currentUser);

if ((string)$theatre['status'] !== 'Draft') {
    $_SESSION['error_message'] = 'Completed theatre records are view-only.';
    header('Location: view.php?id=' . (int)$theatre['id']);
    exit;
}

if (!$permissionService->canEditTheatre($visit, $currentUser)) {
    http_response_code(403);
    exit('Theatre edit is denied.');
}

$latestVitalSigns = $vitalSignsService
    ? $vitalSignsService->getLatestByVisit((int)$visit['id'], $currentUser)
    : null;
$theatreConfiguredFields = $configurableFormService->listFields('theatre_record', true);
$theatreConfiguredValues = $configurableFormService->getResponseValueMap('theatre_record', 'Theatre Record', (int)$theatre['id']);
if (isset($_SESSION['old_configured_fields']) && is_array($_SESSION['old_configured_fields'])) {
    $theatreConfiguredValues = $_SESSION['old_configured_fields'];
    unset($_SESSION['old_configured_fields']);
}

$pageTitle = 'Edit Theatre Record';
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
    <div class="page-header">
        <div>
            <h1>Edit Theatre Record</h1>
            <p><?= e((string)$theatre['visit_number']) ?></p>
        </div>
        <div>
            <a class="btn-secondary" href="view.php?id=<?= (int)$theatre['id'] ?>">Back</a>
        </div>
    </div>
    <?php if ($latestVitalSigns !== null): ?>
        <div class="card">
            <h3>Latest Vital Signs</h3>
            <?php $latest = $latestVitalSigns; require __DIR__ . '/../vital_signs/partials/record_card.php'; ?>
        </div>
    <?php endif; ?>
    <?php $action = 'update.php'; $buttonLabel = 'Update Theatre Record'; require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
