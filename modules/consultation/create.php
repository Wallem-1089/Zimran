<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$visit = consultationRequireVisit($visitService, $visitId);

if (!$permissionService->canCreateConsultation($visit, $currentUser)) {
    http_response_code(403);
    exit('Consultation creation is denied.');
}

if ($consultationService->getByVisit($visitId)) {
    header('Location: index.php?visit=' . $visitId);
    exit;
}

$consultation = $_SESSION['old_consultation'] ?? ['visit_id' => $visitId];
$latestVitalSigns = $vitalSignsService
    ? $vitalSignsService->getLatestByVisit($visitId, $currentUser)
    : null;
unset($_SESSION['old_consultation']);

$pageTitle = 'Start Consultation';
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
            <h1>Start Consultation</h1>
            <p><?= e($visit['visit_number'] ?? ('Encounter #' . $visitId)) ?></p>
        </div>
    </div>
    <?php if ($latestVitalSigns !== null): ?>
        <div class="card">
            <h3>Latest Vital Signs</h3>
            <?php $latest = $latestVitalSigns; require __DIR__ . '/../vital_signs/partials/record_card.php'; ?>
        </div>
    <?php else: ?>
        <div class="card"><h3>Latest Vital Signs</h3><p>No vital signs recorded.</p></div>
    <?php endif; ?>
    <?php $action = 'review.php'; $buttonLabel = 'Review Consultation'; require __DIR__ . '/form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
