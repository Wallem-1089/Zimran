<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$consultationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$consultation = $consultationService->getById($consultationId);
if (!$consultation) {
    http_response_code(404);
    exit('Consultation not found.');
}

$visit = consultationRequireVisit($visitService, (int)$consultation['visit_id']);
if ((string)$consultation['status'] !== 'Draft'
    || !$permissionService->canEditConsultation($visit, $currentUser)
) {
    http_response_code(403);
    exit('This consultation cannot be edited.');
}

$pageTitle = 'Edit Consultation';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert-danger"><?= nl2br(e((string)$_SESSION['error_message'])) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <div class="page-header">
        <div>
            <h1>Edit Consultation</h1>
            <p><?= e((string)$consultation['visit_number']) ?></p>
        </div>
    </div>
    <?php $action = 'update.php'; $buttonLabel = 'Update Draft'; require __DIR__ . '/form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
