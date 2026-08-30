<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/POPService.php';
require_once __DIR__ . '/../../services/VisitService.php';

function popTableExists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table');
        $stmt->execute([':table' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

function popFlash(array $result, string $successMessage): void
{
    if (($result['success'] ?? false) === true) {
        $_SESSION['success_message'] = $successMessage;
        return;
    }
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to complete the POP action.'];
}

function popRequireVisit(VisitService $visitService, int $visitId): array
{
    $visit = $visitService->getVisitById($visitId);
    if (!$visit) {
        http_response_code(404);
        exit('Encounter not found.');
    }
    return $visit;
}

function popRequireCreateAccess(PermissionService $permissionService, array $visit, array $user, string $requestSource): void
{
    if (!$permissionService->canViewEncounter($visit, $user)) {
        http_response_code(403);
        exit('POP access denied.');
    }
    if (!$permissionService->canCreatePopRequest($visit, $user, $requestSource)) {
        http_response_code(403);
        exit('You do not have permission to create this POP request.');
    }
}

function popBackToWorkspace(int $visitId): string
{
    return '../visits/workspace.php?id=' . $visitId . '&tab=pop';
}

function popRequestSourceLabel(string $source): string
{
    return $source === 'Direct' ? 'Direct' : 'Clinical';
}

$visitService = new VisitService($pdo);
$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$popService = new POPService($pdo, null, null, $permissionService);
$popTablesReady = popTableExists($pdo, 'pop_requests') && popTableExists($pdo, 'pop_records');
