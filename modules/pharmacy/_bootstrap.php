<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/ClinicalSafetyService.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/PharmacyService.php';
require_once __DIR__ . '/../../services/StoreService.php';
require_once __DIR__ . '/../../services/VisitService.php';

function pharmacyTableExists(PDO $pdo, string $table): bool
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

function pharmacyFlash(array $result, string $successMessage): void
{
    if (($result['success'] ?? false) === true) {
        $_SESSION['success_message'] = $successMessage;
        return;
    }

    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to complete the pharmacy action.'];
}

function pharmacyRequireVisit(VisitService $visitService, int $visitId): array
{
    $visit = $visitService->getVisitById($visitId);
    if (!$visit) {
        http_response_code(404);
        exit('Encounter not found.');
    }

    return $visit;
}

function pharmacyBackToWorkspace(int $visitId): string
{
    return '../visits/workspace.php?id=' . $visitId . '&tab=pharmacy';
}

function pharmacyBackToConsultation(int $visitId): string
{
    return '../consultation/view.php?visit=' . $visitId;
}

function pharmacyRequestSourceLabel(string $source): string
{
    return strtoupper(trim($source)) === 'DIRECT' ? 'Direct' : 'Clinical';
}

function pharmacyDepartmentId(PDO $pdo): int
{
    static $departmentId = null;

    if ($departmentId !== null) {
        return $departmentId;
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM departments
        WHERE department_name = 'Pharmacy'
        LIMIT 1
    ");
    $stmt->execute();
    $departmentId = (int)($stmt->fetchColumn() ?: 0);

    return $departmentId;
}

function pharmacyInventoryOptions(StoreService $storeService, PermissionService $permissionService, array $user): array
{
    return $storeService->listItems(['status' => 'active'], $user);
}

$visitService = new VisitService($pdo);
$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$storeService = new StoreService($pdo, null, $permissionService);
$clinicalSafetyService = new ClinicalSafetyService($pdo);
$pharmacyService = new PharmacyService(
    $pdo,
    $storeService,
    $clinicalSafetyService,
    null,
    null,
    $permissionService,
    $visitService
);
$pharmacyTablesReady = pharmacyTableExists($pdo, 'prescriptions')
    && pharmacyTableExists($pdo, 'pharmacy_dispensing');
