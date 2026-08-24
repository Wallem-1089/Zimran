<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/ClinicalSafetyService.php';
require_once __DIR__ . '/../../services/DressingRecordService.php';
require_once __DIR__ . '/../../services/MedicalRecordService.php';
require_once __DIR__ . '/../../services/NursingService.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/ProblemListService.php';
require_once __DIR__ . '/../../services/VitalSignsService.php';
require_once __DIR__ . '/../../services/VisitService.php';

function nursingTableExists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table'
        );
        $stmt->execute([':table' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

function nursingRequireVisit(VisitService $service, int $visitId): array
{
    $visit = $service->getVisitById($visitId);
    if (!$visit) {
        http_response_code(404);
        exit('Encounter not found.');
    }
    return $visit;
}

function nursingBackToWorkspace(int $visitId): string
{
    return '../visits/workspace.php?id=' . $visitId . '&tab=nursing';
}

function nursingBackToChart(int $patientId): string
{
    return '../medical_records/chart.php?patient=' . $patientId . '&tab=nursing';
}

function nursingSafetyLink(int $patientId, int $visitId): string
{
    return '../medical_records/chart.php?patient=' . $patientId . '&tab=safety&visit=' . $visitId;
}

function nursingRequireAccess(PermissionService $permissions, array $visit, array $user): void
{
    if (!$permissions->canViewNursing((int)$visit['patient_id'], $user)) {
        http_response_code(403);
        exit('Nursing access denied.');
    }
}

function nursingSafetySummary(
    ClinicalSafetyService $service,
    int $patientId,
    array $user,
    ?int $visitId = null
): array {
    $banner = $service->getSafetyBannerForUser($patientId, $user, $visitId);
    if (!($banner['success'] ?? false)) {
        return ['success' => false, 'items' => [], 'errors' => $banner['errors'] ?? []];
    }

    return [
        'success' => true,
        'items' => $banner['data']['items'] ?? [],
        'errors' => []
    ];
}

$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$visitService = new VisitService($pdo);
$nursingService = new NursingService($pdo, null, null, $permissionService);
$dressingTablesReady = nursingTableExists($pdo, 'dressing_records');
$dressingRecordService = $dressingTablesReady
    ? new DressingRecordService($pdo, null, $permissionService)
    : null;
$vitalSignsTablesReady = nursingTableExists($pdo, 'vital_signs');
$vitalSignsService = $vitalSignsTablesReady ? new VitalSignsService($pdo, null, $permissionService) : null;
$clinicalSafetyService = new ClinicalSafetyService($pdo);
$problemListService = new ProblemListService($pdo);
$medicalRecordService = new MedicalRecordService($pdo);
