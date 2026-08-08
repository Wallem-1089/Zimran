<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$vitalSignsId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$vitalSigns = $vitalSignsService->getById($vitalSignsId);
if (!$vitalSigns) {
    http_response_code(404);
    exit('Vital signs record not found.');
}

$visit = vitalSignsRequireVisit($visitService, (int)$vitalSigns['visit_id']);
if (!$permissionService->canEditVitalSigns($visit, $currentUser)) {
    http_response_code(403);
    exit('Vital signs editing is denied.');
}

$pageTitle = 'Edit Vital Signs';
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
            <h1>Edit Vital Signs</h1>
            <p><?= e((string)$visit['visit_number']) ?></p>
        </div>
    </div>
    <?php $action = 'update.php'; $buttonLabel = 'Update Vital Signs'; require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
