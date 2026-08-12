<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

if (!$billingTablesReady) {
    http_response_code(503);
    exit('Billing tables are not available yet. Apply Migration 033 to enable this section.');
}

$chargeId = (int)($_POST['charge_id'] ?? 0);
$visitId = (int)($_POST['visit_id'] ?? 0);

$result = $billingService->cancelCharge($chargeId, $currentUser);
$_SESSION['success_message'] = $result['success'] ? 'Patient charge cancelled.' : null;
$_SESSION['error_message'] = $result['success'] ? null : implode(' ', (array)($result['errors'] ?? ['Unable to cancel patient charge.']));
$_SESSION['validation_errors'] = $result['success'] ? [] : (array)($result['errors'] ?? []);

header('Location: view.php?visit=' . $visitId);
exit;
