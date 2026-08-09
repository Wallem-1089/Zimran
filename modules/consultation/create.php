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
$latestLaboratoryRequests = $laboratoryService
    ? $laboratoryService->listByVisit($visitId, $currentUser)
    : [];
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
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Laboratory Requests</h3>
                <p>Request tests for this encounter or review completed results.</p>
            </div>
            <?php if ($permissionService->canCreateLaboratoryRequest($visit, $currentUser, 'Clinical')): ?>
                <a class="btn-primary" href="../laboratory/create.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Request Laboratory Test</a>
            <?php endif; ?>
        </div>
        <?php if ($latestLaboratoryRequests === []): ?>
            <p class="text-muted">No laboratory requests recorded.</p>
        <?php else: ?>
            <ul class="clean-list">
                <?php foreach (array_slice($latestLaboratoryRequests, 0, 3) as $request): ?>
                    <li>
                        <a href="../laboratory/view.php?id=<?= (int)$request['id'] ?>">#<?= (int)$request['id'] ?></a>
                        — <?= e((string)$request['tests_requested']) ?>
                        (<?= e((string)$request['status']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php $action = 'review.php'; $buttonLabel = 'Review Consultation'; require __DIR__ . '/form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
