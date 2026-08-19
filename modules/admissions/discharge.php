<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admissionRequireReady($admissionTablesReady);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$admission = $admissionService->getAdmissionById($id, $currentUser);
if (!$admission) {
    http_response_code(404);
    exit('Admission not found.');
}
$visit = $visitService->getVisitById((int)$admission['visit_id']);
if (!$permissionService->canDischargeAdmission($visit, $currentUser)) {
    http_response_code(403);
    exit('Admission discharge is denied.');
}

$pageTitle = 'Discharge Admission';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?><div class="alert-danger"><?= e(implode(' ', (array)$_SESSION['validation_errors'])) ?></div><?php unset($_SESSION['validation_errors']); endif; ?>
    <div class="page-header">
        <div><h1>Discharge Admission</h1><p><?= e((string)$admission['patient_name']) ?> · <?= e((string)$admission['ward_name']) ?> — <?= e((string)$admission['bed_label']) ?></p></div>
        <div><a class="btn-secondary" href="view.php?id=<?= (int)$admission['id'] ?>">Back</a></div>
    </div>
    <form method="post" action="discharge_save.php" class="card form-card">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int)$admission['id'] ?>">
        <div class="form-group">
            <label for="discharge_destination">Discharge Destination</label>
            <input type="text" name="discharge_destination" id="discharge_destination" placeholder="Home, referred facility, etc.">
        </div>
        <div class="form-group">
            <label for="discharge_notes">Discharge Notes</label>
            <textarea name="discharge_notes" id="discharge_notes" rows="6" required></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Discharge</button>
            <a class="btn-secondary" href="view.php?id=<?= (int)$admission['id'] ?>">Cancel</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
