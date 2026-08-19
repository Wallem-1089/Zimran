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
    exit('Admission cancellation is denied.');
}

$pageTitle = 'Cancel Admission';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?><div class="alert-danger"><?= e(implode(' ', (array)$_SESSION['validation_errors'])) ?></div><?php unset($_SESSION['validation_errors']); endif; ?>
    <div class="page-header">
        <div><h1>Cancel Admission</h1><p><?= e((string)$admission['patient_name']) ?> · <?= e((string)$admission['ward_name']) ?> — <?= e((string)$admission['bed_label']) ?></p></div>
        <div><a class="btn-secondary" href="view.php?id=<?= (int)$admission['id'] ?>">Back</a></div>
    </div>
    <form method="post" action="cancel_save.php" class="card form-card">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int)$admission['id'] ?>">
        <div class="alert-danger">Cancelling this admission will release the current bed. It does not cancel the encounter itself.</div>
        <div class="form-group">
            <label for="reason">Cancellation Reason</label>
            <textarea name="reason" id="reason" rows="5" required></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Confirm Cancel Admission</button>
            <a class="btn-secondary" href="view.php?id=<?= (int)$admission['id'] ?>">Keep Admission</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
