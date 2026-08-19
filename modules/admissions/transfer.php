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
if (!$permissionService->canTransferAdmission($visit, $currentUser)) {
    http_response_code(403);
    exit('Admission transfer is denied.');
}
$wards = $admissionService->listWards(true);
$beds = $admissionService->listAvailableBeds();

$pageTitle = 'Transfer Admission';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['validation_errors'])): ?><div class="alert-danger"><?= e(implode(' ', (array)$_SESSION['validation_errors'])) ?></div><?php unset($_SESSION['validation_errors']); endif; ?>
    <div class="page-header">
        <div><h1>Transfer Ward / Bed</h1><p>Current: <?= e((string)$admission['ward_name']) ?> — <?= e((string)$admission['bed_label']) ?></p></div>
        <div><a class="btn-secondary" href="view.php?id=<?= (int)$admission['id'] ?>">Back</a></div>
    </div>
    <form method="post" action="transfer_save.php" class="card form-card">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int)$admission['id'] ?>">
        <div class="form-grid">
            <div class="form-group">
                <label for="ward_id">New Ward</label>
                <select name="ward_id" id="ward_id" required>
                    <option value="">Select ward</option>
                    <?php foreach ($wards as $ward): ?><option value="<?= (int)$ward['id'] ?>"><?= e((string)$ward['ward_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="bed_id">Available Bed</label>
                <select name="bed_id" id="bed_id" required>
                    <option value="">Select bed</option>
                    <?php foreach ($beds as $bed): ?><option value="<?= (int)$bed['id'] ?>"><?= e((string)$bed['ward_name']) ?> — <?= e((string)$bed['bed_label']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="reason">Reason</label>
            <textarea name="reason" id="reason" rows="4"></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Transfer</button>
            <a class="btn-secondary" href="view.php?id=<?= (int)$admission['id'] ?>">Cancel</a>
        </div>
    </form>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
