<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/VitalSignsService.php';
require_once __DIR__ . '/../../services/VisitService.php';

$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$visitService = new VisitService($pdo);
$vitalSignsService = new VitalSignsService($pdo, null, $permissionService);

function vitalSignsRequireVisit(VisitService $service, int $visitId): array
{
    $visit = $service->getVisitById($visitId);
    if (!$visit) {
        http_response_code(404);
        exit('Encounter not found.');
    }

    return $visit;
}

function vitalSignsRequireAccess(PermissionService $permissions, array $visit, array $user): void
{
    if (!$permissions->canViewVitalSigns((int)$visit['patient_id'], $user)) {
        http_response_code(403);
        exit('Vital signs access denied.');
    }
}

function vitalSignsBackToWorkspace(int $visitId): string
{
    return '../visits/workspace.php?id=' . $visitId . '&tab=vitals';
}

function vitalSignsBackToChart(int $patientId): string
{
    return '../medical_records/chart.php?patient=' . $patientId . '&tab=vitals';
}
