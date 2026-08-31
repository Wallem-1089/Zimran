<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/PermissionService.php';

function assertAdminSplit(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertAdminSplit($databaseName === $resolved['test'] && $databaseName !== $resolved['live'], 'Admin split test is not isolated from live database.');

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
$password = password_hash('development-password', PASSWORD_DEFAULT);
$pdo->prepare("
    INSERT INTO users (
        employee_id, first_name, last_name, gender, email, username,
        password, department_id, role_id, status, must_change_password
    )
    SELECT 'DEV-WALTER-001', 'Walter', 'Ikhile', 'Male', 'walter@development.invalid',
           'walter', :password, d.id, r.id, 'Active', 0
    FROM departments d
    INNER JOIN roles r
    WHERE d.department_name = 'Administrator'
      AND r.role_name = 'System Administrator'
    ON DUPLICATE KEY UPDATE
        first_name = VALUES(first_name),
        last_name = VALUES(last_name),
        department_id = VALUES(department_id),
        role_id = VALUES(role_id),
        status = 'Active'
")->execute([':password' => $password]);
$pdo->exec("DELETE FROM schema_migrations WHERE migration_name = '060_superadmin_admin_split_up.sql'");
$manager->apply(__DIR__ . '/../database/migrations/060_superadmin_admin_split_up.sql', 60);

$stmt = $pdo->query("
    SELECT u.username, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('admin', 'walter')
    ORDER BY u.username
");
$users = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $users[(string)$row['username']] = $row;
}

assertAdminSplit(($users['walter']['role_name'] ?? '') === 'Super Administrator', 'Walter was not promoted to Super Administrator.');
assertAdminSplit(($users['walter']['department_name'] ?? '') === 'Super Administrator', 'Walter was not moved to Super Administrator department.');
assertAdminSplit(($users['admin']['role_name'] ?? '') === 'System Administrator', 'Default admin should remain ordinary System Administrator.');

$permissionService = new PermissionService($pdo);
assertAdminSplit($permissionService->isAdministrator($users['walter']), 'Walter should have full superadmin override.');
assertAdminSplit($permissionService->isAdministrationUser($users['walter']), 'Walter should access Administration.');
assertAdminSplit(!$permissionService->isAdministrator($users['admin']), 'Ordinary admin should not have full superadmin override.');
assertAdminSplit($permissionService->isAdministrationUser($users['admin']), 'Ordinary admin should access Administration.');
assertAdminSplit($permissionService->canManageUsers($users['admin']), 'Ordinary admin should manage users.');
assertAdminSplit($permissionService->canViewAllDepartmentWorklists($users['admin']), 'Ordinary admin should view all department worklists.');
assertAdminSplit(!$permissionService->canCreateConsultation(['visit_status' => 'Doctor', 'patient_id' => 1], $users['admin']), 'Ordinary admin should not inherit clinical mutation override.');

fwrite(STDOUT, "Superadmin/admin split tests passed.\n");
