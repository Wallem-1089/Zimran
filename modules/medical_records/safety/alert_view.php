<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$alertId = (int)($_GET['id'] ?? 0);
$alert = clinicalSafetyAlertForUser($clinicalSafetyService, $alertId, $currentUser);
$patientId = (int)$alert['patient_id'];
$visitId = clinicalSafetyVisitContext(
    $pdo,
    $permissionService,
    $currentUser,
    $patientId,
    $_GET['visit'] ?? null
);
$contextQuery = clinicalSafetyQuery($visitId);
$confidentialHidden = !empty($alert['confidential_hidden']);
$canManage = !$confidentialHidden
    && $permissionService->canManageClinicalAlerts($patientId, $currentUser);
$canHistory = $permissionService->canViewClinicalSafetyHistory($patientId, $currentUser);
$success = $_SESSION['success_message'] ?? null;
$errors = $_SESSION['validation_errors'] ?? [];
unset($_SESSION['success_message'], $_SESSION['validation_errors']);

$pageTitle = 'Clinical Alert';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
    <?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
    <main class="content">
        <div class="page-header">
            <h1><?= e((string)$alert['title']) ?></h1>
            <a href="index.php?patient=<?= $patientId ?><?= e($contextQuery) ?>#alerts">Clinical Safety</a>
        </div>
        <?php if ($success): ?><div class="alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($errors): ?><div class="alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
        <div class="card">
            <p><strong>Type:</strong> <?= e((string)$alert['alert_type']) ?></p>
            <p><strong>Priority:</strong> <?= e((string)$alert['priority']) ?></p>
            <p><strong>Confidentiality:</strong> <?= e((string)$alert['confidentiality_level']) ?></p>
            <p><strong>Status:</strong> <?= e((string)$alert['effective_status']) ?></p>
            <p><strong>Reason:</strong> <?= e((string)($alert['reason'] ?? 'Confidential details hidden.')) ?></p>
            <?php if ($canHistory): ?>
                <a href="alert_history.php?id=<?= $alertId ?><?= e($contextQuery) ?>">History</a>
            <?php endif; ?>
            <?php if ($canManage && !empty($alert['is_active'])): ?>
                · <a href="alert_edit.php?id=<?= $alertId ?><?= e($contextQuery) ?>">Edit</a>
            <?php endif; ?>
        </div>
        <?php if ($canManage): ?>
            <div class="card">
                <h2>Alert Status</h2>
                <?php if (!empty($alert['is_active'])): ?>
                    <form method="post" action="alert_close.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $alertId ?>">
                        <input type="hidden" name="version" value="<?= (int)$alert['version'] ?>">
                        <?php if ($visitId !== null): ?><input type="hidden" name="visit_id" value="<?= $visitId ?>"><?php endif; ?>
                        <label for="closure_reason">Closure reason</label>
                        <textarea id="closure_reason" name="reason" required></textarea>
                        <button class="btn-secondary">Close Alert</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="alert_reactivate.php">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $alertId ?>">
                        <input type="hidden" name="version" value="<?= (int)$alert['version'] ?>">
                        <?php if ($visitId !== null): ?><input type="hidden" name="visit_id" value="<?= $visitId ?>"><?php endif; ?>
                        <label for="reactivation_reason">Reactivation reason</label>
                        <textarea id="reactivation_reason" name="reason" required></textarea>
                        <button class="btn-primary">Reactivate Alert</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
