<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/AuditService.php';
require_once __DIR__ . '/../../services/RadiologyService.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/VisitService.php';

function radiologyTableExists(PDO $pdo, string $table): bool
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

function radiologyFlash(array $result, string $successMessage): void
{
    if (($result['success'] ?? false) === true) {
        $_SESSION['success_message'] = $successMessage;
        return;
    }

    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to complete the radiology action.'];
}

function radiologyRequireVisit(VisitService $visitService, int $visitId): array
{
    $visit = $visitService->getVisitById($visitId);
    if (!$visit) {
        http_response_code(404);
        exit('Encounter not found.');
    }
    return $visit;
}

function radiologyRequireAccess(PermissionService $permissionService, array $visit, array $user, string $requestSource): void
{
    if (!$permissionService->canViewEncounter($visit, $user)) {
        http_response_code(403);
        exit('Radiology access denied.');
    }

    if (!$permissionService->canCreateRadiologyRequest($visit, $user, $requestSource)) {
        http_response_code(403);
        exit('You do not have permission to create this radiology request.');
    }
}

function radiologyBackToWorkspace(int $visitId): string
{
    return '../visits/workspace.php?id=' . $visitId . '&tab=radiology';
}

function radiologyBackToConsultation(int $visitId): string
{
    return '../consultation/view.php?visit=' . $visitId;
}

function radiologyRequestSourceLabel(string $source): string
{
    return $source === 'Direct' ? 'Direct' : 'Clinical';
}

$visitService = new VisitService($pdo);
$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$radiologyService = new RadiologyService($pdo, null, null, $permissionService);
$radiologyTablesReady = radiologyTableExists($pdo, 'radiology_requests')
    && radiologyTableExists($pdo, 'radiology_reports');

