<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PatientRegistrationBillingService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: search.php');
    exit;
}

requireCsrfToken();

$patientId = (int)($_POST['patient_id'] ?? 0);
$familyCardId = (int)($_POST['family_card_id'] ?? 0);
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);

if (!$currentUser) {
    $_SESSION['error_message'] = 'Your session has expired.';
    header('Location: ../../authentication/login.php');
    exit;
}

$permissionService = new PermissionService($pdo);
if (!$permissionService->canRegisterPatient($currentUser)
    && !$permissionService->canCreateBillingRequest($currentUser)
    && !$permissionService->canReviewBillingRequest($currentUser)
    && !$permissionService->canRecordPayment($currentUser)
) {
    http_response_code(403);
    exit('You are not allowed to request Family Card renewal.');
}

$service = new PatientRegistrationBillingService($pdo);
$result = $service->ensureFamilyCardRenewalRequest($familyCardId, $currentUser);

if (empty($result['success'])) {
    $_SESSION['error_message'] = implode(' ', $result['errors'] ?? ['Unable to create Family Card renewal request.']);
} elseif (!empty($result['created'])) {
    $_SESSION['success_message'] = 'Family Card renewal billing request created and sent to Accounts.';
} else {
    $_SESSION['success_message'] = 'A pending Family Card renewal billing request already exists.';
}

header('Location: view.php?id=' . $patientId);
exit;
