<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$_SERVER['PHP_SELF'] = '/test/sidebar_visibility_test.php';
$baseUrl = '/hospital_management_system';

function sidebarUser(string $role, string $department): array
{
    return [
        'id' => 0,
        'first_name' => 'Sidebar',
        'last_name' => 'Tester',
        'role_name' => $role,
        'department_name' => $department,
        'department_id' => 0,
        'active_department_name' => $department,
        'active_department_id' => 0,
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

$adminSidebar = renderSidebarFor(sidebarUser('System Administrator', 'Administrator'));
foreach (['Medical Records', 'Accounts', 'Store', 'Admissions', 'Pharmacy', 'Billing', 'Reports', 'Administration', 'Switch Department'] as $label) {
    assertSidebarContains($adminSidebar, $label, 'Administrator');
}

$doctorSidebar = renderSidebarFor(sidebarUser('Doctor', 'Doctor'));
assertSidebarContains($doctorSidebar, 'Admissions', 'Doctor');
foreach (['Accounts', 'Store', 'Pharmacy', 'Billing', 'Reports', 'Medical Records', 'Administration'] as $label) {
    assertSidebarOmits($doctorSidebar, $label, 'Doctor');
}

$nurseSidebar = renderSidebarFor(sidebarUser('Nurse', 'Nursing'));
assertSidebarContains($nurseSidebar, 'Admissions', 'Nurse');
foreach (['Accounts', 'Store', 'Pharmacy', 'Billing', 'Reports', 'Medical Records', 'Administration'] as $label) {
    assertSidebarOmits($nurseSidebar, $label, 'Nurse');
}

$recordsSidebar = renderSidebarFor(sidebarUser('Records Officer', 'Records'));
foreach (['Medical Records', 'Admissions', 'Reports'] as $label) {
    assertSidebarContains($recordsSidebar, $label, 'Records Officer');
}
foreach (['Accounts', 'Store', 'Pharmacy', 'Billing', 'Administration'] as $label) {
    assertSidebarOmits($recordsSidebar, $label, 'Records Officer');
}

$accountsSidebar = renderSidebarFor(sidebarUser('Accountant', 'Accounts'));
foreach (['Accounts', 'Billing', 'Reports'] as $label) {
    assertSidebarContains($accountsSidebar, $label, 'Accountant');
}
foreach (['Medical Records', 'Store', 'Admissions', 'Pharmacy', 'Administration'] as $label) {
    assertSidebarOmits($accountsSidebar, $label, 'Accountant');
}

$storeSidebar = renderSidebarFor(sidebarUser('Store Officer', 'Store'));
foreach (['Store', 'Reports'] as $label) {
    assertSidebarContains($storeSidebar, $label, 'Store Officer');
}
foreach (['Medical Records', 'Accounts', 'Admissions', 'Pharmacy', 'Billing', 'Administration'] as $label) {
    assertSidebarOmits($storeSidebar, $label, 'Store Officer');
}

$pharmacySidebar = renderSidebarFor(sidebarUser('Pharmacist', 'Pharmacy'));
assertSidebarContains($pharmacySidebar, 'Pharmacy', 'Pharmacist');
foreach (['Medical Records', 'Accounts', 'Store', 'Admissions', 'Billing', 'Reports', 'Administration'] as $label) {
    assertSidebarOmits($pharmacySidebar, $label, 'Pharmacist');
}

echo "Sidebar visibility test passed.\n";
