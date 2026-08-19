<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admissionRequireReady($admissionTablesReady);
admissionRequireView($permissionService, $currentUser);

$wards = $admissionService->listWards(false);
$departments = admissionDepartments($pdo);
$pageTitle = 'Wards and Beds';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['success_message'])): ?><div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div><?php unset($_SESSION['success_message']); endif; ?>
    <?php if (isset($_SESSION['validation_errors'])): ?><div class="alert-danger"><?= e(implode(' ', (array)$_SESSION['validation_errors'])) ?></div><?php unset($_SESSION['validation_errors']); endif; ?>
    <div class="page-header">
        <div><h1>Wards & Beds</h1><p>Basic inpatient locations and occupancy.</p></div>
        <div><a class="btn-secondary" href="index.php">Admission Census</a></div>
    </div>

    <?php if ($permissionService->canManageWardsBeds($currentUser)): ?>
        <div class="card">
            <h3>Create Ward</h3>
            <form method="post" action="ward_save.php" class="form-grid">
                <?= csrfField() ?>
                <div class="form-group"><label>Ward Name</label><input type="text" name="ward_name" required></div>
                <div class="form-group"><label>Ward Code</label><input type="text" name="ward_code" required></div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id"><option value="">None</option><?php foreach ($departments as $department): ?><option value="<?= (int)$department['id'] ?>"><?= e((string)$department['department_name']) ?></option><?php endforeach; ?></select>
                </div>
                <div class="form-group"><label>Description</label><input type="text" name="description"></div>
                <div class="form-actions"><button class="btn-primary" type="submit">Create Ward</button></div>
            </form>
        </div>
    <?php endif; ?>

    <div class="card">
        <?php if ($wards === []): ?><div class="empty-state">No wards configured.</div><?php else: ?>
            <table class="data-table"><thead><tr><th>Ward</th><th>Code</th><th>Department</th><th>Total Beds</th><th>Available</th><th>Occupied</th><th>Add Bed</th></tr></thead><tbody>
                <?php foreach ($wards as $ward): ?>
                    <tr>
                        <td><?= e((string)$ward['ward_name']) ?></td>
                        <td><?= e((string)$ward['ward_code']) ?></td>
                        <td><?= e((string)($ward['department_name'] ?? '-')) ?></td>
                        <td><?= (int)($ward['total_beds'] ?? 0) ?></td>
                        <td><?= (int)($ward['available_beds'] ?? 0) ?></td>
                        <td><?= (int)($ward['occupied_beds'] ?? 0) ?></td>
                        <td>
                            <?php if ($permissionService->canManageWardsBeds($currentUser)): ?>
                                <form method="post" action="bed_save.php" class="inline-form">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="ward_id" value="<?= (int)$ward['id'] ?>">
                                    <input type="text" name="bed_label" placeholder="Bed label" required>
                                    <button class="btn-secondary btn-sm" type="submit">Add Bed</button>
                                </form>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
