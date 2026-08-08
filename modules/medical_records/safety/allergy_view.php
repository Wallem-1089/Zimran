<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$allergy = $clinicalSafetyService->getAllergyById((int)($_GET['id'] ?? 0));
if (!$allergy) {
    http_response_code(404);
    exit('Allergy not found.');
}
$patientId = (int)$allergy['patient_id'];
if (!$permissionService->canViewClinicalSafety($patientId, $currentUser)) {
    clinicalSafetyAccessDenied($permissionService, $currentUser, $patientId);
}
$patient = $patientService->getPatientById($patientId);
$visitId = clinicalSafetyVisitContext(
    $pdo,
    $permissionService,
    $currentUser,
    $patientId,
    $_GET['visit'] ?? null
);
$contextQuery = clinicalSafetyQuery($visitId);
$accessResult = $clinicalSafetyService->recordSafetyView(
    $patientId,
    (int)($currentUser['id'] ?? 0),
    $visitId
);
if (!($accessResult['success'] ?? false)) {
    clinicalSafetyAuditFailure();
}
$canUpdate = $permissionService->canUpdateAllergies($patientId, $currentUser);
$canVerify = $permissionService->canVerifyAllergies($patientId, $currentUser);
$canResolve = $permissionService->canResolveAllergies($patientId, $currentUser);
$canDeactivate = $permissionService->canDeactivateAllergies($patientId, $currentUser);
$canReactivate = $permissionService->canReactivateAllergies($patientId, $currentUser);
$canHistory = $permissionService->canViewClinicalSafetyHistory($patientId, $currentUser);
$success = $_SESSION['success_message'] ?? null;
$errors = $_SESSION['validation_errors'] ?? [];
unset($_SESSION['success_message'], $_SESSION['validation_errors']);

$pageTitle = 'Allergy Record';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
    <?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
    <main class="content">
        <div class="page-header">
            <h1><?= e((string)$allergy['substance']) ?></h1>
            <a href="index.php?patient=<?= $patientId ?><?= e($contextQuery) ?>#allergies">Clinical Safety</a>
        </div>
        <?php if ($success): ?><div class="alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($errors): ?><div class="alert-danger"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
        <div class="card">
            <p><strong>Type:</strong> <?= e((string)$allergy['allergy_type']) ?></p>
            <p><strong>Reaction:</strong> <?= e((string)($allergy['reaction'] ?? '-')) ?></p>
            <p><strong>Severity:</strong> <?= e((string)$allergy['severity']) ?></p>
            <p><strong>Status:</strong> <?= e((string)$allergy['clinical_status']) ?> · <?= e((string)$allergy['verification_status']) ?></p>
            <p><strong>Notes:</strong> <?= e((string)($allergy['notes'] ?? '-')) ?></p>
            <?php if ($canHistory): ?><a href="allergy_history.php?id=<?= (int)$allergy['id'] ?><?= e($contextQuery) ?>">History</a><?php endif; ?>
            <?php if ($canUpdate && $allergy['clinical_status'] === 'Active'): ?> · <a href="allergy_edit.php?id=<?= (int)$allergy['id'] ?><?= e($contextQuery) ?>">Edit</a><?php endif; ?>
        </div>

        <?php if ($allergy['clinical_status'] === 'Active'): ?>
            <div class="card"><h2>Clinical Actions</h2>
                <?php if ($canVerify && $allergy['verification_status'] !== 'Confirmed'): ?>
                    <form method="post" action="allergy_verify.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$allergy['id'] ?>"><input type="hidden" name="version" value="<?= (int)$allergy['version'] ?>"><?php if ($visitId !== null): ?><input type="hidden" name="visit_id" value="<?= $visitId ?>"><?php endif; ?><label>Verification reason</label><textarea name="reason" required></textarea><button class="btn-primary">Verify</button></form>
                <?php endif; ?>
                <?php if ($canResolve): ?>
                    <form method="post" action="allergy_resolve.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$allergy['id'] ?>"><input type="hidden" name="version" value="<?= (int)$allergy['version'] ?>"><?php if ($visitId !== null): ?><input type="hidden" name="visit_id" value="<?= $visitId ?>"><?php endif; ?><label>Resolution reason</label><textarea name="reason" required></textarea><button class="btn-secondary">Resolve</button></form>
                    <form method="post" action="allergy_entered_error.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$allergy['id'] ?>"><input type="hidden" name="version" value="<?= (int)$allergy['version'] ?>"><?php if ($visitId !== null): ?><input type="hidden" name="visit_id" value="<?= $visitId ?>"><?php endif; ?><label>Entered-in-error reason</label><textarea name="reason" required></textarea><button class="btn-secondary">Mark Entered in Error</button></form>
                <?php endif; ?>
                <?php if ($canDeactivate): ?>
                    <form method="post" action="allergy_deactivate.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$allergy['id'] ?>"><input type="hidden" name="version" value="<?= (int)$allergy['version'] ?>"><?php if ($visitId !== null): ?><input type="hidden" name="visit_id" value="<?= $visitId ?>"><?php endif; ?><label>Deactivation reason</label><textarea name="reason" required></textarea><button class="btn-secondary">Deactivate</button></form>
                <?php endif; ?>
            </div>
        <?php elseif ($allergy['clinical_status'] === 'Inactive' && $canReactivate): ?>
            <div class="card"><h2>Clinical Actions</h2><form method="post" action="allergy_reactivate.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$allergy['id'] ?>"><input type="hidden" name="version" value="<?= (int)$allergy['version'] ?>"><?php if ($visitId !== null): ?><input type="hidden" name="visit_id" value="<?= $visitId ?>"><?php endif; ?><label>Reactivation reason</label><textarea name="reason" required></textarea><button class="btn-primary">Reactivate</button></form></div>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
