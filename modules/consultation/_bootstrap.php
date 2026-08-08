<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/ConsultationService.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/VitalSignsService.php';
require_once __DIR__ . '/../../services/VisitService.php';

function consultationTableExists(PDO $pdo, string $table): bool
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

$consultationService = new ConsultationService($pdo);
$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$visitService = new VisitService($pdo);
$vitalSignsTablesReady = consultationTableExists($pdo, 'vital_signs');
$vitalSignsService = $vitalSignsTablesReady ? new VitalSignsService($pdo, null, $permissionService) : null;

function consultationFlash(array $result, string $success): void
{
    if ($result['success'] ?? false) {
        $_SESSION['success_message'] = $success;
        return;
    }
    $_SESSION['validation_errors'] = $result['errors'] ?? ['The consultation operation failed.'];
}

function consultationBackToWorkspace(int $visitId): string
{
    return '../visits/workspace.php?id=' . $visitId . '&tab=consultation';
}

function consultationRequireVisit(VisitService $service, int $visitId): array
{
    $visit = $service->getVisitById($visitId);
    if (!$visit) {
        http_response_code(404);
        exit('Encounter not found.');
    }
    return $visit;
}

function consultationRequireAccess(PermissionService $permissions, array $visit, array $user): void
{
    if (!$permissions->canViewConsultation($visit, $user)) {
        http_response_code(403);
        exit('Consultation access denied.');
    }
}
