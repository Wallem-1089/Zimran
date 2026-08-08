<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();
$documentId = (int)($_POST['document_id'] ?? 0);
$document = $medicalDocumentService->getDocumentById($documentId);
if (!$document) {
    http_response_code(404);
    exit('Medical Document not found.');
}
$visitId = documentVisitContext($pdo, $permissionService, $currentUser, (int)$document['patient_id'], $_POST['visit_id'] ?? $document['visit_id'] ?? null);
$result = $medicalDocumentService->replaceDocument($documentId, $_POST, $_FILES['document_file'] ?? [], $currentUser, (int)($_POST['version'] ?? 0));
documentFlash($result, 'Medical Document replacement uploaded.');
header('Location: ' . (($result['success'] ?? false) ? 'view.php?id=' . $documentId : 'replace.php?id=' . $documentId) . documentContextQuery($visitId));
exit;
