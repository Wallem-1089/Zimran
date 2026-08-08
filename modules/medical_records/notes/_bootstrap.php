<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../services/ClinicalNoteService.php';
require_once __DIR__ . '/../../../services/PatientService.php';
require_once __DIR__ . '/../../../services/PermissionService.php';
require_once __DIR__ . '/../../../services/VisitService.php';

$clinicalNoteService = new ClinicalNoteService($pdo);
$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);

function noteVisitContext(PDO $pdo, PermissionService $permissions, array $user, int $patientId, mixed $candidate): ?int
{
    $visitId = filter_var($candidate, FILTER_VALIDATE_INT);
    if (!$visitId) {
        return null;
    }
    $visit = (new VisitService($pdo))->getVisitById((int)$visitId);
    if (!$visit || (int)$visit['patient_id'] !== $patientId || !$permissions->canViewEncounter($visit, $user)) {
        $permissions->logPatientDenied((int)($user['id'] ?? 0), $patientId, 'CLINICAL_NOTE_ACCESS_DENIED', 'Invalid Clinical Note encounter context was rejected.');
        http_response_code(403);
        exit('The encounter context is invalid or inaccessible.');
    }
    return (int)$visitId;
}

function noteContextQuery(?int $visitId): string
{
    return $visitId === null ? '' : '&visit=' . $visitId;
}

function noteFlash(array $result, string $success): void
{
    if ($result['success'] ?? false) {
        $_SESSION['success_message'] = $success;
    } else {
        $_SESSION['validation_errors'] = $result['errors'] ?? ['The Clinical Note operation failed.'];
    }
}

function noteForUser(ClinicalNoteService $service, int $noteId, array $user, bool $audit = true): array
{
    $result = $service->getNoteByIdForUser($noteId, $user, $audit);
    if ($result['success'] ?? false) {
        return $result['data']['note'];
    }
    http_response_code(!empty($result['audit_failed']) ? 503 : (!empty($result['forbidden']) ? 403 : 404));
    exit(!empty($result['audit_failed']) ? 'Protected Clinical Note information is temporarily unavailable.' : 'Clinical Note access is denied.');
}

function noteTypeLabel(string $key): string
{
    return ucwords(str_replace('_', ' ', $key));
}
