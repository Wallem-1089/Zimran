<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: search.php');
    exit;
}

requireCsrfToken();

$patientId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$reason = trim((string)($_POST['reason'] ?? ''));

if ($patientId <= 0) {
    $_SESSION['error_message'] = 'Invalid patient selected.';
    header('Location: search.php');
    exit;
}

$permissionService = new PermissionService($pdo);
if (!$permissionService->canDeletePatient($patientId, $currentUser)) {
    http_response_code(403);
    exit('You do not have permission to delete this patient.');
}

$patientService = new PatientService($pdo);
$result = $patientService->deletePatient(
    $patientId,
    (int)($currentUser['id'] ?? 0),
    $reason
);

if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to delete patient.'];
    header('Location: view.php?id=' . $patientId);
    exit;
}

$_SESSION['success_message'] = 'Patient registration deleted/voided. Future patient and encounter numbers are unaffected.';
header('Location: search.php');
exit;
