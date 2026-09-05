<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admissionRequireReady($admissionTablesReady);

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
$visit = $visitService->getVisitById($visitId);
if (!$visit) {
    http_response_code(404);
    exit('Encounter not found.');
}
if (!$permissionService->canCreateAdmission($visit, $currentUser)) {
    http_response_code(403);
    exit('Admission creation is denied.');
}
if ($admissionService->getByVisit($visitId, $currentUser)) {
    header('Location: view.php?visit=' . $visitId);
    exit;
}

$wards = $admissionService->listWards(true);
$beds = $admissionService->listAvailableBeds();
$admission = $_SESSION['old_admission'] ?? [];
unset($_SESSION['old_admission']);
$admissionConfiguredFields = $configurableFormService->listFields('admission_record', true);
$admissionConfiguredValues = $_SESSION['old_configured_fields'] ?? [];
unset($_SESSION['old_configured_fields']);

$pageTitle = 'Admit Patient';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?><div class="alert-danger"><?= e(implode(' ', (array)$_SESSION['validation_errors'])) ?></div><?php unset($_SESSION['validation_errors']); endif; ?>
    <div class="page-header">
        <div><h1>Admit Patient</h1><p><?= e((string)($visit['visit_number'] ?? 'Encounter')) ?></p></div>
        <div><a class="btn-secondary" href="<?= e(admissionBackToWorkspace($visitId)) ?>">Workspace</a></div>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
