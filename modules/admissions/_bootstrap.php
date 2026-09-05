<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/AdmissionService.php';
require_once __DIR__ . '/../../services/ConfigurableFormService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/VisitService.php';

function admissionTableExists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :table
        ');
        $stmt->execute([':table' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

function admissionTablesReady(PDO $pdo): bool
{
    return admissionTableExists($pdo, 'wards')
        && admissionTableExists($pdo, 'ward_beds')
        && admissionTableExists($pdo, 'admissions')
        && admissionTableExists($pdo, 'admission_movements');
}

function admissionFlash(array $result, string $success): void
{
    if (($result['success'] ?? false) === true) {
        $_SESSION['success_message'] = $success;
        return;
    }

    $_SESSION['validation_errors'] = $result['errors'] ?? ['Admission action failed.'];
}

function admissionRequireReady(bool $ready): void
{
    if (!$ready) {
        http_response_code(503);
        exit('Admission tables are not available yet. Apply Migration 037 to enable this section.');
    }
}

function admissionRequireView(PermissionService $permissionService, array $currentUser): void
{
    if (!$permissionService->canViewAdmissions($currentUser)) {
        http_response_code(403);
        exit('Admission access denied.');
    }
}

function admissionDepartments(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT id, department_name
        FROM departments
        WHERE is_active = 1
        ORDER BY department_name
    ');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function admissionBackToWorkspace(int $visitId): string
{
    return '../visits/workspace.php?id=' . $visitId . '&tab=admission';
}

$permissionService = new PermissionService($pdo);
$configurableFormService = new ConfigurableFormService($pdo, $permissionService);
$visitService = new VisitService($pdo);
$admissionTablesReady = admissionTablesReady($pdo);
$admissionService = $admissionTablesReady ? new AdmissionService($pdo, null, null, $permissionService) : null;
