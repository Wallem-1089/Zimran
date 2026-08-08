<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$allergy = $clinicalSafetyService->getAllergyById((int)($_GET['id'] ?? 0));
if (!$allergy) { http_response_code(404); exit('Allergy not found.'); }
if (!$permissionService->canViewClinicalSafetyHistory((int)$allergy['patient_id'], $currentUser)) { clinicalSafetyAccessDenied($permissionService, $currentUser, (int)$allergy['patient_id']); }
$visitId = clinicalSafetyVisitContext($pdo, $permissionService, $currentUser, (int)$allergy['patient_id'], $_GET['visit'] ?? null);
$accessResult = $clinicalSafetyService->recordSafetyView(
    (int)$allergy['patient_id'],
    (int)($currentUser['id'] ?? 0),
    $visitId
);
if (!($accessResult['success'] ?? false)) {
    clinicalSafetyAuditFailure();
}
$history = $clinicalSafetyService->getAllergyHistory((int)$allergy['id']);
$historyFields = [
    'allergy_type' => 'Allergy type',
    'substance' => 'Substance',
    'reaction' => 'Reaction',
    'severity' => 'Severity',
    'clinical_status' => 'Clinical status',
    'verification_status' => 'Verification status',
    'onset_date' => 'Onset date',
    'notes' => 'Notes'
];
$pageTitle = 'Allergy History'; $moduleStylesheet = '/modules/medical_records/assets/medical_records.css'; require __DIR__ . '/../../../layouts/header.php'; require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content"><div class="page-header"><h1>Allergy History</h1><a href="allergy_view.php?id=<?= (int)$allergy['id'] ?><?= e(clinicalSafetyQuery($visitId)) ?>">Return to Allergy</a></div><div class="card"><?php if ($history === []): ?><p>No history is available.</p><?php endif; ?><?php foreach ($history as $historyEntry): ?><?php require __DIR__ . '/../partials/clinical_history_diff.php'; ?><?php endforeach; ?></div></main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
