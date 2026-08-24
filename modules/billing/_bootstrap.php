<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

require_once __DIR__ . '/../../services/AccountsService.php';
require_once __DIR__ . '/../../services/BillingService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/VisitService.php';

function billingTableExists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :table'
        );
        $stmt->execute([':table' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

$billingTablesReady = billingTableExists($pdo, 'patient_charges')
    && billingTableExists($pdo, 'invoices')
    && billingTableExists($pdo, 'payments');
$billingRequestsReady = billingTableExists($pdo, 'billing_requests');

$permissionService = new PermissionService($pdo);
$billingService = new BillingService($pdo);
$accountsService = new AccountsService($pdo);
$visitService = new VisitService($pdo);

function billingRequireAccess(PermissionService $permissionService, ?array $user): void
{
    if (!$permissionService->canViewBilling($user)) {
        http_response_code(403);
        exit('You are not allowed to view billing.');
    }
}

function billingRequireRequestAccess(PermissionService $permissionService, ?array $user): void
{
    if (!$permissionService->canViewBillingRequests($user)) {
        http_response_code(403);
        exit('You are not allowed to view billing requests.');
    }
}

function billingDisplayPatientName(array $row, string $fallback = 'Unknown Patient'): string
{
    $name = trim((string)($row['patient_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }

    $firstName = trim((string)($row['first_name'] ?? ''));
    $lastName = trim((string)($row['last_name'] ?? ''));
    $combined = trim($firstName . ' ' . $lastName);

    return $combined !== '' ? $combined : $fallback;
}
