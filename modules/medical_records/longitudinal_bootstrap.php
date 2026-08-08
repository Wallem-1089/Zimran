<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/ProblemListService.php';
require_once __DIR__ . '/../../services/VisitService.php';

$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$problemListService = new ProblemListService($pdo);

function longitudinalAccessDenied(
    PermissionService $permissionService,
    array $currentUser,
    int $patientId,
    string $event
): never {
    if ($patientId > 0) {
        $permissionService->logPatientDenied(
            (int)($currentUser['id'] ?? 0),
            $patientId,
            $event,
            'Longitudinal clinical-record access was denied.'
        );
    }
    http_response_code(403);
    exit('You do not have permission to perform this action.');
}

function longitudinalVisitContext(
    PDO $pdo,
    PermissionService $permissionService,
    array $currentUser,
    int $patientId,
    mixed $candidate
): ?int {
    $visitId = filter_var($candidate, FILTER_VALIDATE_INT);
    if (!$visitId) {
        return null;
    }
    $visit = (new VisitService($pdo))->getVisitById((int)$visitId);
    if (!$visit
        || (int)$visit['patient_id'] !== $patientId
        || !$permissionService->canViewEncounter($visit, $currentUser)
    ) {
        longitudinalAccessDenied(
            $permissionService,
            $currentUser,
            $patientId,
            'MEDICAL_HISTORY_ACCESS_DENIED'
        );
    }
    return (int)$visitId;
}

function longitudinalQuery(?int $visitId): string
{
    return $visitId === null ? '' : '&visit=' . $visitId;
}

function longitudinalDepartmentId(array $user): ?int
{
    $id = (int)($user['active_department_id'] ?? $user['department_id'] ?? 0);
    return $id > 0 ? $id : null;
}

function longitudinalFlash(array $result, string $successMessage): void
{
    $_SESSION[$result['success'] ? 'success_message' : 'validation_errors'] =
        $result['success'] ? $successMessage : ($result['errors'] ?? ['Operation failed.']);
}

function longitudinalProblemForUser(
    ProblemListService $service,
    int $id,
    array $user
): array {
    $result = $service->getProblemByIdForUser($id, $user);
    if (!($result['success'] ?? false)) {
        http_response_code(!empty($result['forbidden']) ? 403 : (!empty($result['audit_failed']) ? 503 : 404));
        exit(!empty($result['audit_failed'])
            ? 'Protected medical information is temporarily unavailable.'
            : 'Problem record is unavailable.');
    }
    return $result['data']['problem'];
}

function longitudinalHistoryForUser(
    ProblemListService $service,
    int $id,
    array $user
): array {
    $result = $service->getHistoryEntryByIdForUser($id, $user);
    if (!($result['success'] ?? false)) {
        http_response_code(!empty($result['forbidden']) ? 403 : (!empty($result['audit_failed']) ? 503 : 404));
        exit(!empty($result['audit_failed'])
            ? 'Protected medical information is temporarily unavailable.'
            : 'Medical-history record is unavailable.');
    }
    return $result['data']['entry'];
}
