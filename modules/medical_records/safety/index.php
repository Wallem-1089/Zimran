<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$patientId = filter_input(INPUT_GET, 'patient', FILTER_VALIDATE_INT);
$patient = $patientId ? $patientService->getPatientById($patientId) : null;
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}
if (!$permissionService->canViewClinicalSafety((int)$patientId, $currentUser)) {
    $permissionService->logPatientDenied(
        (int)($currentUser['id'] ?? 0),
        (int)$patientId,
        'CLINICAL_SAFETY_ACCESS_DENIED',
        'User attempted to view clinical safety information without authorization.'
    );
    http_response_code(403);
    exit('You do not have permission to view clinical safety information.');
}

$canViewConfidential = $permissionService->canViewConfidentialAlerts((int)$patientId, $currentUser);
$canRecordAllergies = $permissionService->canRecordAllergies((int)$patientId, $currentUser);
$canManageAlerts = $permissionService->canManageClinicalAlerts((int)$patientId, $currentUser);
$visitId = clinicalSafetyVisitContext(
    $pdo,
    $permissionService,
    $currentUser,
    (int)$patientId,
    $_GET['visit'] ?? null
);
$contextQuery = clinicalSafetyQuery($visitId);
$allergies = $clinicalSafetyService->getPatientAllergies((int)$patientId, true);
$alerts = $clinicalSafetyService->getPatientAlertsForUser((int)$patientId, $currentUser, true);
$safetyBanner = $clinicalSafetyService->getSafetyBannerForUser(
    (int)$patientId,
    $currentUser,
    $visitId
);
if (!($safetyBanner['success'] ?? false)) {
    clinicalSafetyAuditFailure();
}
$safetyBannerUrl = '../chart.php?patient=' . (int)$patientId . '&tab=safety' . $contextQuery;

$successMessage = $_SESSION['success_message'] ?? null;
$errors = $_SESSION['validation_errors'] ?? [];
unset($_SESSION['success_message'], $_SESSION['validation_errors']);

$pageTitle = 'Clinical Safety';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
    <?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
    <main class="content">
        <div class="page-header">
            <div>
                <h1>Clinical Safety</h1>
                <p><?= e($patient['hospital_number']) ?> — <?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></p>
            </div>
            <div>
                <a href="../chart.php?patient=<?= (int)$patientId ?>&tab=safety<?= e($contextQuery) ?>">Return to Patient Chart</a>
                <?php if ($visitId !== null): ?>
                    · <a href="../../visits/workspace.php?id=<?= $visitId ?>">Return to Workspace</a>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($successMessage): ?><div class="alert-success"><?= e($successMessage) ?></div><?php endif; ?>
        <?php if ($errors): ?><div class="alert-danger"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php require __DIR__ . '/../partials/clinical_safety_banner.php'; ?>

        <section class="card" id="allergies">
            <div class="page-header">
                <h2>Allergies</h2>
                <?php if ($canRecordAllergies): ?><a class="btn-primary" href="allergy_create.php?patient=<?= (int)$patientId ?><?= e($contextQuery) ?>">Record Allergy</a><?php endif; ?>
            </div>
            <?php if ($allergies === []): ?><p>No structured allergy records.</p><?php endif; ?>
            <?php foreach ($allergies as $allergy): ?>
                <div class="history-entry">
                    <strong><?= e($allergy['substance']) ?></strong>
                    <p><?= e($allergy['allergy_type']) ?> · <?= e($allergy['severity']) ?> · <?= e($allergy['clinical_status']) ?> · <?= e($allergy['verification_status']) ?></p>
                    <a href="allergy_view.php?id=<?= (int)$allergy['id'] ?><?= e($contextQuery) ?>">View</a>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="card" id="alerts">
            <div class="page-header">
                <h2>Clinical Alerts</h2>
                <?php if ($canManageAlerts): ?><a class="btn-primary" href="alert_create.php?patient=<?= (int)$patientId ?><?= e($contextQuery) ?>">Create Alert</a><?php endif; ?>
            </div>
            <?php if ($alerts === []): ?><p>No clinical alert records.</p><?php endif; ?>
            <?php foreach ($alerts as $alert): ?>
                <div class="history-entry">
                    <strong><?= e($alert['title']) ?></strong>
                    <p><?= e($alert['alert_type']) ?> · <?= e($alert['priority']) ?> · <?= e($alert['effective_status']) ?> · <?= e($alert['confidentiality_level']) ?></p>
                    <a href="alert_view.php?id=<?= (int)$alert['id'] ?><?= e($contextQuery) ?>">View</a>
                </div>
            <?php endforeach; ?>
        </section>
    </main>
    <?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
