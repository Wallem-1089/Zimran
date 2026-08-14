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
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Physiotherapy</h3>
                <p>Refer the patient for physiotherapy or review existing records.</p>
            </div>
            <?php if ($physiotherapyService && $permissionService->canCreatePhysiotherapyRequest($visit, $currentUser, 'Clinical')): ?>
                <a class="btn-primary" href="../physiotherapy/request.php?visit=<?= (int)$visit['id'] ?>&source=Clinical">Refer to Physiotherapy</a>
            <?php endif; ?>
        </div>
        <?php if (!$physiotherapyService): ?>
            <p class="text-muted">Physiotherapy tables are not available yet.</p>
        <?php else: ?>
            <?php
                $latestPhysiotherapyRecords = $physiotherapyService->listByVisit((int)$visit['id'], $currentUser);
                $latestPhysiotherapyRecord = $latestPhysiotherapyRecords[0] ?? null;
                $latestPhysiotherapySession = $latestPhysiotherapyRecord
                    ? $physiotherapyService->getResult((int)$latestPhysiotherapyRecord['id'], $currentUser)
                    : null;
            ?>
            <?php if ($latestPhysiotherapyRecords === []): ?>
                <p class="text-muted">No physiotherapy records recorded.</p>
            <?php else: ?>
                <ul class="clean-list">
                    <?php foreach (array_slice($latestPhysiotherapyRecords, 0, 3) as $record): ?>
                        <li>
                            <a href="../physiotherapy/view.php?id=<?= (int)$record['id'] ?>">#<?= (int)$record['id'] ?></a>
                            — <?= e((string)($record['presenting_problem'] ?? $record['summary'] ?? '')) ?>
                            (<?= e((string)$record['status']) ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($latestPhysiotherapySession !== null && trim((string)($latestPhysiotherapySession['treatment_given'] ?? '')) !== ''): ?>
                    <div class="summary-grid">
                        <div class="summary-item"><span class="summary-label">Treatment Given</span> <span class="summary-value"><?= e((string)($latestPhysiotherapySession['treatment_given'] ?? '-')) ?></span></div>
                        <div class="summary-item"><span class="summary-label">Patient Response</span> <span class="summary-value"><?= e((string)($latestPhysiotherapySession['patient_response'] ?? '-')) ?></span></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="card-header">
            <div>
                <h3>Theatre</h3>
                <p>Open or create a simple theatre record for this encounter.</p>
            </div>
            <?php if ($theatreService && $permissionService->canCreateTheatre($visit, $currentUser)): ?>
                <?php $existingTheatre = $theatreService->getByVisit((int)$visit['id'], $currentUser); ?>
                <a class="btn-primary" href="<?= $existingTheatre ? '../theatre/view.php?id=' . (int)$existingTheatre['id'] : '../theatre/create.php?visit=' . (int)$visit['id'] ?>">
                    <?= $existingTheatre ? 'Open Theatre' : 'Start Theatre Record' ?>
                </a>
            <?php endif; ?>
        </div>
        <?php if (!$theatreService): ?>
            <p class="text-muted">Theatre tables are not available yet.</p>
        <?php else: ?>
            <?php $existingTheatre = $theatreService->getByVisit((int)$visit['id'], $currentUser); ?>
            <?php if ($existingTheatre === null): ?>
                <p class="text-muted">No theatre record recorded.</p>
            <?php else: ?>
                <div class="summary-grid">
                    <div class="summary-item"><span class="summary-label">Procedure</span> <span class="summary-value"><?= e((string)($existingTheatre['procedure_name'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Surgeon</span> <span class="summary-value"><?= e((string)($existingTheatre['surgeon_name'] ?? '-')) ?></span></div>
                    <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)($existingTheatre['status'] ?? '-')) ?></span></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php $action = 'review.php'; $buttonLabel = 'Review Consultation'; $enableWritingMode = true; require __DIR__ . '/form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
