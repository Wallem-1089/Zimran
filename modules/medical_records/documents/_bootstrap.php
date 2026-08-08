<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../services/MedicalDocumentService.php';
require_once __DIR__ . '/../../../services/PatientService.php';
require_once __DIR__ . '/../../../services/PermissionService.php';
require_once __DIR__ . '/../../../services/VisitService.php';

$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$medicalDocumentService = new MedicalDocumentService($pdo);

function documentAccessDenied(
    PermissionService $permissionService,
    array $user,
    int $patientId,
    string $message = 'You do not have permission to access this Medical Document.'
): never {
    if ($patientId > 0) {
        $permissionService->logPatientDenied(
            (int)($user['id'] ?? 0),
            $patientId,
            'DOCUMENT_ACCESS_DENIED',
            'Medical Document access was denied by policy.'
        );
    }
    http_response_code(403);
    exit($message);
}

function documentVisitContext(
    PDO $pdo,
    PermissionService $permissionService,
    array $user,
    int $patientId,
    mixed $candidate
): ?int {
    $visitId = filter_var($candidate, FILTER_VALIDATE_INT);
    if (!$visitId) {
        return null;
    }
    $visit = (new VisitService($pdo))->getVisitById((int)$visitId);
    $recordsAccess = $permissionService->isAdministrator($user)
        || (string)($user['role_name'] ?? '') === 'Records Officer'
        || (string)($user['department_name'] ?? '') === 'Records';
    if (!$visit
        || (int)$visit['patient_id'] !== $patientId
        || (!$recordsAccess && !$permissionService->canViewEncounter($visit, $user))
    ) {
        documentAccessDenied($permissionService, $user, $patientId);
    }
    return (int)$visitId;
}

function documentContextQuery(?int $visitId): string
{
    return $visitId === null ? '' : '&visit=' . $visitId;
}

function documentFlash(array $result, string $successMessage): void
{
    if ($result['success'] ?? false) {
        $_SESSION['success_message'] = $successMessage;
        return;
    }
    $_SESSION['validation_errors'] = $result['errors'] ?? ['The operation failed.'];
}

function documentForUser(
    MedicalDocumentService $service,
    int $documentId,
    array $user,
    bool $auditAccess = true
): array {
    $result = $service->getDocumentByIdForUser($documentId, $user, $auditAccess);
    if ($result['success'] ?? false) {
        return $result['data']['document'];
    }
    http_response_code(!empty($result['audit_failed']) ? 503 : (!empty($result['forbidden']) ? 403 : 404));
    exit(!empty($result['audit_failed'])
        ? 'Protected document information is temporarily unavailable.'
        : (!empty($result['forbidden']) ? 'Medical Document access is denied.' : 'Medical Document not found.'));
}

function documentTypeLabel(string $key): string
{
    return ucwords(str_replace('_', ' ', $key));
}

function documentFormatBytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return number_format($bytes / 1048576, 1) . ' MB';
}
