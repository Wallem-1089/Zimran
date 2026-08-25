<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PatientStockUsageService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/VisitService.php';

function patientStockUsageTableExists(PDO $pdo): bool
{
    try {
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = "patient_stock_usage"
        ');
        $stmt->execute();
        return (int)$stmt->fetchColumn() === 1;
    } catch (Throwable) {
        return false;
    }
}

function patientStockUsageDepartments(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT id, department_name
        FROM departments
        WHERE is_active = 1
        ORDER BY department_name ASC
    ');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function patientStockUsageDefaultDepartment(array $visit, array $currentUser): int
{
    return (int)(
        $currentUser['active_department_id']
        ?? $_SESSION['active_department_id']
        ?? $currentUser['department_id']
        ?? $visit['current_department_id']
        ?? 0
    );
}

function patientStockUsageBackToWorkspace(int $visitId): string
{
    return '../visits/workspace.php?id=' . $visitId . '&tab=stock_usage';
}

$permissionService = new PermissionService($pdo);
$patientService = new PatientService($pdo);
$visitService = new VisitService($pdo);
$patientStockUsageService = new PatientStockUsageService($pdo, null, null, null, $permissionService);
$patientStockUsageTablesReady = patientStockUsageTableExists($pdo);
