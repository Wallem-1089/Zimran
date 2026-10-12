<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/PatientRegistrationBillingService.php';

$permissionService = new PermissionService($pdo);
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);

$familyCardCanManage = $currentUser
    && (
        $permissionService->isAdministrator($currentUser)
        || in_array((string)($currentUser['role_name'] ?? ''), ['Records Officer', 'Receptionist'], true)
        || in_array((string)($currentUser['active_department_name'] ?? $currentUser['department_name'] ?? ''), ['Records', 'Reception'], true)
    );

if (!$familyCardCanManage) {
    http_response_code(403);
    exit('You are not allowed to manage Family Cards.');
}

function familyCardTablesReady(PDO $pdo): bool
{
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute([':table_name' => 'family_cards']);
        if (!$stmt->fetchColumn()) {
            return false;
        }
        $stmt->execute([':table_name' => 'family_card_members']);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable) {
        return false;
    }
}
