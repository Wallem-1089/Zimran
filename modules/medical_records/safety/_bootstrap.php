<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../services/ClinicalSafetyService.php';
require_once __DIR__ . '/../../../services/PatientService.php';
require_once __DIR__ . '/../../../services/PermissionService.php';
require_once __DIR__ . '/../../../services/SettingsService.php';
require_once __DIR__ . '/../../../services/VisitService.php';

$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$settingsService = new SettingsService($pdo);
$clinicalSafetyService = new ClinicalSafetyService(
    $pdo,
    null,
    null,
    $settingsService,
    $permissionService
);

function clinicalSafetyAccessDenied(
    PermissionService $permissionService,
    array $currentUser,
    int $patientId
): void {
    if ($patientId > 0) {
        $permissionService->logPatientDenied(
            (int)($currentUser['id'] ?? 0),
            $patientId,
            'CLINICAL_SAFETY_ACCESS_DENIED',
            'Clinical Safety access was denied by the authorization policy.'
        );
    }

    http_response_code(403);
    exit('You do not have permission to perform this Clinical Safety action.');
}

function clinicalSafetyVisitContext(
    PDO $pdo,
    PermissionService $permissionService,
    array $currentUser,
    int $patientId,
    mixed $visitId
): ?int {
    $visitId = filter_var($visitId, FILTER_VALIDATE_INT);
    if (!$visitId) {
        return null;
    }

    $visit = (new VisitService($pdo))->getVisitById((int)$visitId);
    if (!$visit
        || (int)$visit['patient_id'] !== $patientId
        || !$permissionService->canViewEncounter($visit, $currentUser)
    ) {
        clinicalSafetyAccessDenied($permissionService, $currentUser, $patientId);
    }

    return (int)$visitId;
}

function clinicalSafetyQuery(?int $visitId): string
{
    return $visitId === null ? '' : '&visit=' . $visitId;
}

function clinicalSafetyAlertForUser(
    ClinicalSafetyService $service,
    int $alertId,
    array $currentUser,
    bool $auditAccess = true
): array {
    $result = $service->getAlertByIdForUser($alertId, $currentUser, $auditAccess);
    if ($result['success'] ?? false) {
        return $result['data']['alert'];
    }

    if (!empty($result['audit_failed'])) {
        clinicalSafetyAuditFailure();
    }
    http_response_code(!empty($result['forbidden']) ? 403 : 404);
    exit(!empty($result['forbidden'])
        ? 'You do not have permission to view this clinical alert.'
        : 'Clinical alert not found.');
}

function clinicalSafetyAuditFailure(): void
{
    http_response_code(503);
    exit('Clinical Safety information is temporarily unavailable because access could not be recorded.');
}
