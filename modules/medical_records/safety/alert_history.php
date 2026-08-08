<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$alertId = (int)($_GET['id'] ?? 0);
$historyResult = $clinicalSafetyService->getAlertHistoryForUser($alertId, $currentUser);
if (!($historyResult['success'] ?? false)) {
    if (!empty($historyResult['forbidden'])) {
        http_response_code(403);
        exit('You do not have permission to view clinical alert history.');
    }
    clinicalSafetyAuditFailure();
}
$alert = $historyResult['data']['alert'];
$history = $historyResult['data']['history'];
$visitId = clinicalSafetyVisitContext($pdo, $permissionService, $currentUser, (int)$alert['patient_id'], $_GET['visit'] ?? null);
$historyFields = [
    'alert_type' => 'Alert type',
    'title' => 'Title',
    'reason' => 'Clinical reason',
    'priority' => 'Priority',
    'confidentiality_level' => 'Confidentiality',
    'is_active' => 'Active status',
    'starts_at' => 'Starts at',
    'expires_at' => 'Expires at',
    'closure_reason' => 'Closure reason'
];
$pageTitle = 'Clinical Alert History'; $moduleStylesheet = '/modules/medical_records/assets/medical_records.css'; require __DIR__ . '/../../../layouts/header.php'; require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content"><div class="page-header"><h1>Clinical Alert History</h1><a href="alert_view.php?id=<?= $alertId ?><?= e(clinicalSafetyQuery($visitId)) ?>">Return to Alert</a></div><div class="card"><?php if ($history === []): ?><p>No history is available.</p><?php endif; ?><?php foreach ($history as $historyEntry): ?><?php require __DIR__ . '/../partials/clinical_history_diff.php'; ?><?php endforeach; ?></div></main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
