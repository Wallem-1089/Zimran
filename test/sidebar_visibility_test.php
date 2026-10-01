<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../config/helpers.php';

$_SERVER['PHP_SELF'] = '/test/sidebar_visibility_test.php';
$baseUrl = '/hospital_management_system';

function sidebarUser(string $role, string $department): array
{
    $departmentIds = [
        'Administrator' => 1,
        'Reception' => 2,
        'Records' => 3,
        'Doctor' => 4,
        'Nursing' => 5,
        'Laboratory' => 6,
        'Pharmacy' => 7,
        'Physiotherapy' => 8,
        'X-Ray' => 9,
        'Theatre' => 10,
        'Accounts' => 11,
        'Store' => 12,
        'Orderly' => 13,
        'ECG' => 14,
        'Plaster' => 15,
        'Super Administrator' => 16,
    ];
    $departmentId = $departmentIds[$department] ?? 0;

    return [
        'id' => 9000 + $departmentId,
        'first_name' => 'Sidebar',
        'last_name' => 'Tester',
        'role_name' => $role,
        'department_name' => $department,
        'department_id' => $departmentId,
        'active_department_name' => $department,
        'active_department_id' => $departmentId,
    ];
}

function renderSidebarFor(array $user): string
{
    global $pdo, $baseUrl;

    $currentUser = $user;

    ob_start();
    require __DIR__ . '/../layouts/sidebar.php';
    return (string)ob_get_clean();
}

function assertSidebarContains(string $html, string $label, string $context): void
{
    if (strpos($html, '>' . $label . '<') === false && strpos($html, $label) === false) {
        throw new RuntimeException($context . ' should show "' . $label . '" in the sidebar.');
    }
}

function assertSidebarOmits(string $html, string $label, string $context): void
{
    if (strpos($html, '>' . $label . '<') !== false || strpos($html, $label) !== false) {
        throw new RuntimeException($context . ' should not show "' . $label . '" in the sidebar.');
    }
}

$superAdminSidebar = renderSidebarFor(sidebarUser('Super Administrator', 'Super Administrator'));
foreach (['Medical Records', 'Price Catalogue', 'Store', 'Admissions', 'Pharmacy', 'Billing', 'Emergency', 'Reports', 'Administration', 'Switch Department'] as $label) {
    assertSidebarContains($superAdminSidebar, $label, 'Super Administrator');
}

$adminSidebar = renderSidebarFor(sidebarUser('System Administrator', 'Administrator'));
foreach (['Department Worklist', 'Emergency', 'Reports', 'Administration'] as $label) {
    assertSidebarContains($adminSidebar, $label, 'Administrator');
}
foreach (['Medical Records', 'Price Catalogue', 'Store', 'Admissions', 'Pharmacy', 'Billing'] as $label) {
    assertSidebarOmits($adminSidebar, $label, 'Administrator');
}

$doctorSidebar = renderSidebarFor(sidebarUser('Doctor', 'Doctor'));
assertSidebarContains($doctorSidebar, 'Admissions', 'Doctor');
foreach (['Price Catalogue', 'Store', 'Pharmacy', 'Billing', 'Reports', 'Medical Records', 'Administration'] as $label) {
    assertSidebarOmits($doctorSidebar, $label, 'Doctor');
}

$nurseSidebar = renderSidebarFor(sidebarUser('Nurse', 'Nursing'));
assertSidebarContains($nurseSidebar, 'Admissions', 'Nurse');
foreach (['Price Catalogue', 'Store', 'Pharmacy', 'Billing', 'Reports', 'Medical Records', 'Administration'] as $label) {
    assertSidebarOmits($nurseSidebar, $label, 'Nurse');
}

$recordsSidebar = renderSidebarFor(sidebarUser('Records Officer', 'Records'));
foreach (['Medical Records', 'Admissions', 'Reports'] as $label) {
    assertSidebarContains($recordsSidebar, $label, 'Records Officer');
}
foreach (['Price Catalogue', 'Store', 'Pharmacy', 'Billing', 'Administration'] as $label) {
    assertSidebarOmits($recordsSidebar, $label, 'Records Officer');
}

$accountsSidebar = renderSidebarFor(sidebarUser('Accountant', 'Accounts'));
foreach (['Price Catalogue', 'Billing', 'Reports'] as $label) {
    assertSidebarContains($accountsSidebar, $label, 'Accountant');
}
foreach (['Medical Records', 'Store', 'Admissions', 'Pharmacy', 'Administration'] as $label) {
    assertSidebarOmits($accountsSidebar, $label, 'Accountant');
}

$storeSidebar = renderSidebarFor(sidebarUser('Store Officer', 'Store'));
foreach (['Store', 'Reports'] as $label) {
    assertSidebarContains($storeSidebar, $label, 'Store Officer');
}
foreach (['Medical Records', 'Price Catalogue', 'Admissions', 'Pharmacy', 'Billing', 'Administration'] as $label) {
    assertSidebarOmits($storeSidebar, $label, 'Store Officer');
}

$pharmacySidebar = renderSidebarFor(sidebarUser('Pharmacist', 'Pharmacy'));
foreach (['Pharmacy', 'Price Catalogue', 'Store'] as $label) {
    assertSidebarContains($pharmacySidebar, $label, 'Pharmacist');
}
foreach (['Medical Records', 'Admissions', 'Billing', 'Reports', 'Administration'] as $label) {
    assertSidebarOmits($pharmacySidebar, $label, 'Pharmacist');
}

$orderlySidebar = renderSidebarFor(sidebarUser('Orderly', 'Orderly'));
assertSidebarContains($orderlySidebar, 'Stock Requests', 'Orderly');
foreach (['Medical Records', 'Laboratory', 'Radiology', 'Physiotherapy', 'Theatre', 'Price Catalogue', 'Store', 'Admissions', 'Pharmacy', 'Billing', 'Reports', 'Administration'] as $label) {
    assertSidebarOmits($orderlySidebar, $label, 'Orderly');
}

echo "Sidebar visibility test passed.\n";
