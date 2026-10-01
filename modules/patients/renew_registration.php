<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PatientRegistrationBillingService.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: search.php');
    exit;
}

requireCsrfToken();

$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
if (!$currentUser) {
    $_SESSION['error_message'] = 'Your session has expired.';
    header('Location: ../../authentication/login.php');
    exit;
}

$patientId = (int)($_POST['patient_id'] ?? 0);
if ($patientId <= 0) {
    $_SESSION['error_message'] = 'Patient was not selected for registration renewal.';
    header('Location: search.php');
    exit;
}

$permissionService = new PermissionService($pdo);
if (!$permissionService->canRegisterPatient($currentUser)
    && !$permissionService->canCreateBillingRequest($currentUser)
    && !$permissionService->canReviewBillingRequest($currentUser)
    && !$permissionService->canRecordPayment($currentUser)
) {
    http_response_code(403);
    exit('You are not allowed to request patient registration renewal.');
}

$patientService = new PatientService($pdo);
$patient = $patientService->getPatientById($patientId);
if (!$patient) {
    $_SESSION['error_message'] = 'Patient not found.';
    header('Location: search.php');
    exit;
}

$registrationGate = $patientService->getRegistrationGateStatus($patientId);
if (($registrationGate['status'] ?? '') !== 'Expired') {
    $_SESSION['error_message'] = 'Registration renewal can only be requested after the current registration has expired.';
    header('Location: view.php?id=' . $patientId);
    exit;
}

$registrationBillingService = new PatientRegistrationBillingService($pdo);
$result = $registrationBillingService->ensureRenewalRequest($patientId, $currentUser);

if (empty($result['success'])) {
    $_SESSION['error_message'] = implode(' ', $result['errors'] ?? ['Unable to create renewal billing request.']);
} elseif (!empty($result['created'])) {
    $_SESSION['success_message'] = 'Monthly renewal billing request created and sent to Accounts.';
} else {
    $_SESSION['success_message'] = 'A pending monthly renewal billing request already exists for this patient.';
}

header('Location: view.php?id=' . $patientId);
exit;
