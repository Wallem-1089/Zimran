<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$visit = vitalSignsRequireVisit($visitService, $visitId);

if (!$permissionService->canCreateVitalSigns($visit, $currentUser)) {
    http_response_code(403);
    exit('Vital signs creation is denied.');
}

$pageTitle = 'Record Vital Signs';
$moduleStylesheet = '/modules/visits/assets/visits.css';
$latestVitalSigns = $vitalSignsService->getLatestByVisit($visitId, $currentUser);
$vitalSigns = $_SESSION['old_vital_signs'] ?? ['visit_id' => $visitId, 'patient_id' => (int)$visit['patient_id']];
unset($_SESSION['old_vital_signs']);

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
            <h1>Record Vital Signs</h1>
            <p><?= e((string)($visit['visit_number'] ?? ('Encounter #' . $visitId))) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Patient</span><span class="summary-value"><?= e((string)$visit['first_name']) ?> <?= e((string)$visit['last_name']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Hospital Number</span><span class="summary-value"><?= e((string)$visit['hospital_number']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Department</span><span class="summary-value"><?= e((string)($visit['department_name'] ?? '')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Encounter Status</span><span class="summary-value"><?= e((string)($visit['visit_status'] ?? 'Unknown')) ?></span></div>
        </div>
    </div>

    <?php if ($latestVitalSigns !== null): ?>
        <div class="card">
            <h3>Latest Vital Signs</h3>
            <?php $latest = $latestVitalSigns; require __DIR__ . '/partials/record_card.php'; ?>
        </div>
    <?php else: ?>
        <div class="card"><h3>Latest Vital Signs</h3><p>No vital signs recorded.</p></div>
    <?php endif; ?>

    <?php $action = 'save.php'; $buttonLabel = 'Save Vital Signs'; require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
