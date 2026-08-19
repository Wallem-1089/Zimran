<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
admissionRequireReady($admissionTablesReady);
admissionRequireView($permissionService, $currentUser);

$admissions = $admissionService->listActive($currentUser);
$pageTitle = 'Admissions';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (isset($_SESSION['success_message'])): ?><div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div><?php unset($_SESSION['success_message']); endif; ?>
    <div class="page-header">
        <div><h1>Admissions</h1><p>Current inpatient census.</p></div>
        <div>
            <?php if ($permissionService->canManageWardsBeds($currentUser)): ?>
                <a class="btn-secondary" href="wards.php">Wards & Beds</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <?php if ($admissions === []): ?>
            <div class="empty-state">No active admissions.</div>
        <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Patient</th><th>Hospital No.</th><th>Visit</th><th>Ward</th><th>Bed</th><th>Status</th><th>Admitted</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($admissions as $row): ?>
                        <tr>
                            <td><?= e((string)$row['patient_name']) ?></td>
                            <td><?= e((string)$row['hospital_number']) ?></td>
                            <td><?= e((string)$row['visit_number']) ?></td>
                            <td><?= e((string)$row['ward_name']) ?></td>
                            <td><?= e((string)$row['bed_label']) ?></td>
                            <td><?= e((string)$row['status']) ?></td>
                            <td><?= e((string)$row['admitted_at']) ?></td>
                            <td class="table-actions">
                                <a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$row['id'] ?>">View</a>
                                <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$row['visit_id'] ?>&tab=admission">Workspace</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
