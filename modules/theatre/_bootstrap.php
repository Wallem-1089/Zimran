<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/TheatreService.php';
require_once __DIR__ . '/../../services/VitalSignsService.php';
require_once __DIR__ . '/../../services/VisitService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

function theatreTableExists(PDO $pdo, string $table): bool
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

$visitService = new VisitService($pdo);
$permissionService = new PermissionService($pdo);
$theatreTablesReady = theatreTableExists($pdo, 'theatre_records');
$theatreService = $theatreTablesReady ? new TheatreService($pdo, null, null, $permissionService) : null;
$vitalSignsTablesReady = theatreTableExists($pdo, 'vital_signs');
$vitalSignsService = $vitalSignsTablesReady ? new VitalSignsService($pdo, null, $permissionService) : null;

function theatreFlash(array $result, string $successMessage): void
{
    if (($result['success'] ?? false) === true) {
        $_SESSION['success_message'] = $successMessage;
        return;
    }

    $_SESSION['validation_errors'] = $result['errors'] ?? ['The theatre action failed.'];
}

function theatreBackToWorkspace(int $visitId): string
{
    return '../visits/workspace.php?id=' . $visitId . '&tab=theatre';
}

function theatreRequireVisit(VisitService $service, int $visitId): array
{
    $visit = $service->getVisitById($visitId);
    if (!$visit) {
        http_response_code(404);
        exit('Encounter not found.');
    }
    return $visit;
}

function theatreRequireAccess(PermissionService $permissions, array $visit, array $user): void
{
    if (!$permissions->canViewTheatre($visit, $user)) {
        http_response_code(403);
        exit('Theatre access denied.');
    }
}

