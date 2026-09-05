<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

requireCsrfToken();

$visitId = filter_input(INPUT_POST, 'visit_id', FILTER_VALIDATE_INT) ?: 0;
$patientId = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT) ?: 0;
$recordSource = physiotherapyRequestSourceLabel((string)($_POST['record_source'] ?? 'Clinical'));

if (!$visitId) {
    header('Location: index.php');
    exit;
}

$visit = physiotherapyRequireVisit($visitService, $visitId);
physiotherapyRequireAccess($permissionService, $visit, $currentUser, $recordSource);

if (!$physiotherapyTablesReady) {
    http_response_code(503);
    exit('Physiotherapy tables are not available yet. Apply Migration 028 to enable this section.');
}

$result = $physiotherapyService->createRecord($_POST, $currentUser);
if (($result['success'] ?? false) !== true) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to save physiotherapy record.'];
    $_SESSION['old_physiotherapy_request'] = [
        'record_source' => $recordSource,
        'referral_reason' => (string)($_POST['referral_reason'] ?? ''),
        'presenting_problem' => (string)($_POST['presenting_problem'] ?? ''),
        'assessment' => (string)($_POST['assessment'] ?? ''),
        'functional_limitations' => (string)($_POST['functional_limitations'] ?? ''),
        'treatment_plan' => (string)($_POST['treatment_plan'] ?? ''),
        'goals' => (string)($_POST['goals'] ?? ''),
        'precautions' => (string)($_POST['precautions'] ?? ''),
    ];
    $_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
    header('Location: request.php?visit=' . $visitId . '&source=' . urlencode($recordSource));
    exit;
}

$configuredResult = $configurableFormService->saveResponse(
    'physiotherapy_record',
    (int)$visit['patient_id'],
    (int)$visit['id'],
    'Physiotherapy Record',
    (int)$result['physiotherapy_record_id'],
    $_POST,
    $currentUser
);
if (($configuredResult['success'] ?? false) !== true) {
    $_SESSION['validation_errors'] = $configuredResult['errors'] ?? ['Unable to save configured form fields.'];
    $_SESSION['old_configured_fields'] = $_POST['configured_fields'] ?? [];
    header('Location: edit.php?id=' . (int)$result['physiotherapy_record_id']);
    exit;
}

$_SESSION['success_message'] = 'Physiotherapy record saved.';
header('Location: view.php?id=' . (int)$result['physiotherapy_record_id']);
exit;
