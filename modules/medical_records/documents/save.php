<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();
$patientId = (int)($_POST['patient_id'] ?? 0);
$patient = $patientService->getPatientById($patientId);
if (!$patient) {
    http_response_code(404);
    exit('Patient not found.');
}
$visitId = documentVisitContext($pdo, $permissionService, $currentUser, $patientId, $_POST['visit_id'] ?? null);
$_POST['visit_id'] = $visitId;
$result = $medicalDocumentService->uploadDocument(
    $_POST,
    $_FILES['document_file'] ?? [],
    $currentUser
);
documentFlash($result, 'Medical Document uploaded.');
$target = ($result['success'] ?? false)
    ? 'view.php?id=' . (int)$result['data']['document_id']
    : 'upload.php?patient=' . $patientId;
header('Location: ' . $target . documentContextQuery($visitId));
exit;
