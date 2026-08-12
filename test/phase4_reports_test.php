<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
require_once __DIR__ . '/../database/tools/MigrationManager.php';
require_once __DIR__ . '/../services/DashboardService.php';
require_once __DIR__ . '/../services/PermissionService.php';

function assertReports(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertReports(
    $databaseName === $resolved['test'] && $databaseName !== $resolved['live'],
    'Phase 4.5 reports tests are not isolated from the live database.'
);

fwrite(
    STDOUT,
    'Resolved live database: ' . $resolved['live'] . PHP_EOL .
    'Resolved test database: ' . $resolved['test'] . PHP_EOL
);

$manager = new MigrationManager($pdo, $databaseName);
$manager->ensureLedger();
foreach ([30, 31, 32, 33, 34] as $migration) {
    $files = glob(__DIR__ . '/../database/migrations/' . sprintf('%03d', $migration) . '_*_up.sql');
    assertReports(isset($files[0]), 'Missing migration ' . $migration . '.');
    $manager->apply($files[0], $migration);
}

$storeRoleId = (int)$pdo->query("SELECT id FROM roles WHERE role_name = 'Store Officer' LIMIT 1")->fetchColumn();
$storeDepartmentId = (int)$pdo->query("SELECT id FROM departments WHERE department_name = 'Store' LIMIT 1")->fetchColumn();
assertReports($storeRoleId > 0 && $storeDepartmentId > 0, 'Store role or department is missing.');

$existingStoreUserId = (int)$pdo->query("SELECT id FROM users WHERE username = 'dev_store' LIMIT 1")->fetchColumn();
if ($existingStoreUserId <= 0) {
    $stmt = $pdo->prepare("
        INSERT INTO users (
            employee_id, first_name, last_name, gender, username, password,
            department_id, role_id, status, password_changed_at
        ) VALUES (
            'DEV-STORE-REPORTS', 'Development', 'Store', 'Male', 'dev_store', :password,
            :department_id, :role_id, 'Active', NOW()
        )
    ");
    $stmt->execute([
        ':password' => password_hash('admin1234', PASSWORD_DEFAULT),
        ':department_id' => $storeDepartmentId,
        ':role_id' => $storeRoleId,
    ]);
}

$users = [];
$rows = $pdo->query("
    SELECT u.*, r.role_name, d.department_name
    FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    INNER JOIN departments d ON d.id = u.department_id
    WHERE u.username IN ('admin','dev_accounts','dev_doctor','dev_store')
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $users[$row['username']] = $row;
}

foreach (['admin', 'dev_accounts', 'dev_doctor', 'dev_store'] as $username) {
    assertReports(isset($users[$username]), 'Missing fixture user ' . $username . '.');
}

$permissionService = new PermissionService($pdo);
assertReports($permissionService->canViewReports($users['admin']), 'Administrator should view reports.');
assertReports($permissionService->canViewReports($users['dev_accounts']), 'Accounts should view reports.');
assertReports($permissionService->canViewFinancialReports($users['dev_accounts']), 'Accounts should view financial reports.');
assertReports(!$permissionService->canViewFinancialReports($users['dev_doctor']), 'Doctor should not view financial reports.');
assertReports($permissionService->canViewClinicalReports($users['dev_doctor']), 'Doctor should view clinical reports.');
assertReports($permissionService->canViewInventoryReports($users['dev_store']), 'Store should view inventory reports.');

$dashboardService = new DashboardService($pdo);
$dashboard = $dashboardService->getAdministratorDashboard();
assertReports(($dashboard['success'] ?? false) === true, 'Administrator dashboard failed to load.');
foreach (['clinical', 'financial', 'inventory', 'notifications'] as $key) {
    assertReports(isset($dashboard['data'][$key]), 'Dashboard missing ' . $key . ' summary.');
}

$today = ['date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d')];
$activity = $dashboardService->getPatientEncounterActivity($today);
$clinical = $dashboardService->getClinicalActivityReport($today);
$financial = $dashboardService->getFinancialReport($today);
$inventory = $dashboardService->getInventoryReport($today);

assertReports(isset($activity['summary'], $activity['by_department']), 'Encounter activity report contract is incomplete.');
assertReports(isset($clinical['items']) && is_array($clinical['items']), 'Clinical report contract is incomplete.');
assertReports(array_key_exists('payments', $financial), 'Financial report missing payment total.');
assertReports(isset($inventory['transactions'], $inventory['balances']), 'Inventory report contract is incomplete.');

$sidebar = file_get_contents(__DIR__ . '/../layouts/sidebar.php');
assertReports(str_contains((string)$sidebar, '/modules/reports/index.php'), 'Sidebar missing Reports destination.');

foreach (['clinical.php', 'financial.php', 'inventory.php', 'activity.php'] as $page) {
    $contents = (string)file_get_contents(__DIR__ . '/../modules/reports/' . $page);
    assertReports(!str_contains($contents, 'clinical_notes'), 'Report page should not expose clinical note narratives: ' . $page);
    assertReports(!str_contains($contents, 'laboratory_results.result'), 'Report page should not expose laboratory result text: ' . $page);
    assertReports(!str_contains($contents, 'radiology_reports.findings'), 'Report page should not expose radiology report text: ' . $page);
}

fwrite(STDOUT, 'PASS: Phase 4.5 Reports regression passed.' . PHP_EOL);
