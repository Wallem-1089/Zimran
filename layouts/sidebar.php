<?php

declare(strict_types=1);

if (!isset($currentUser)) {
    $currentUser = null;
}

$currentPage = basename($_SERVER['PHP_SELF']);

$canAccessMedicalRecordsSidebar = false;
$canAccessAccountsSidebar = false;
$canAccessStoreSidebar = false;
$canAccessAdmissionsSidebar = false;
$canAccessPharmacySidebar = false;
$canAccessBillingSidebar = false;
$canAccessReportsSidebar = false;
$canAccessDepartmentSwitchSidebar = false;
$departmentNotificationCount = 0;
$userNotificationCount = 0;

if ($currentUser && isset($pdo)) {
    require_once __DIR__ . '/../services/PermissionService.php';
    require_once __DIR__ . '/../services/DepartmentNotificationService.php';
    require_once __DIR__ . '/../services/UserNotificationService.php';
    $sidebarPermissionService = new PermissionService($pdo);
    $sidebarRole = (string)($currentUser['role_name'] ?? '');
    $sidebarDepartment = (string)(
        $currentUser['active_department_name']
        ?? $_SESSION['active_department_name']
        ?? $currentUser['department_name']
        ?? ''
    );
    $sidebarIsAdmin = $sidebarPermissionService->isAdministrator($currentUser);
    $sidebarRoleIn = static fn(array $roles): bool => in_array($sidebarRole, $roles, true);
    $sidebarDepartmentIn = static fn(array $departments): bool => in_array($sidebarDepartment, $departments, true);

    $canAccessMedicalRecordsSidebar = $sidebarIsAdmin
        || $sidebarRoleIn(['Records Officer'])
        || $sidebarDepartmentIn(['Records']);
    $canAccessAccountsSidebar = $sidebarIsAdmin
        || $sidebarRoleIn(['Accountant', 'Accounts'])
        || $sidebarDepartmentIn(['Accounts']);
    $canAccessStoreSidebar = $sidebarIsAdmin
        || $sidebarRoleIn(['Store Officer'])
        || $sidebarDepartmentIn(['Store']);
    $canAccessAdmissionsSidebar = $sidebarIsAdmin
        || $sidebarRoleIn(['Receptionist', 'Records Officer', 'Doctor', 'Nurse'])
        || $sidebarDepartmentIn(['Reception', 'Records', 'Doctor', 'Nursing']);
    $canAccessPharmacySidebar = $sidebarIsAdmin
        || $sidebarRoleIn(['Pharmacist'])
        || $sidebarDepartmentIn(['Pharmacy']);
    $canAccessBillingSidebar = $sidebarIsAdmin
        || $sidebarRoleIn(['Accountant', 'Accounts'])
        || $sidebarDepartmentIn(['Accounts']);
    $canAccessReportsSidebar = $sidebarIsAdmin
        || $sidebarRoleIn(['Accountant', 'Accounts', 'Store Officer', 'Records Officer'])
        || $sidebarDepartmentIn(['Accounts', 'Store', 'Records']);

    $sidebarDepartmentId = (int)(
        $currentUser['active_department_id']
        ?? $_SESSION['active_department_id']
        ?? $currentUser['department_id']
        ?? 0
    );
    if ($sidebarDepartmentId > 0) {
        $departmentNotificationCount = (new DepartmentNotificationService($pdo))->getUnreadCount($sidebarDepartmentId);
    }
    try {
        $userNotificationCount = (new UserNotificationService($pdo))->getUnreadCount((int)($currentUser['id'] ?? 0));
    } catch (Throwable) {
        $userNotificationCount = 0;
    }

    if ($sidebarIsAdmin) {
        $canAccessDepartmentSwitchSidebar = true;
    } else {
        try {
            $stmt = $pdo->prepare('
                SELECT COUNT(*)
                FROM user_departments ud
                INNER JOIN departments d ON d.id = ud.department_id
                WHERE ud.user_id = :user_id
                  AND ud.is_active = 1
                  AND d.is_active = 1
            ');
            $stmt->execute([':user_id' => (int)($currentUser['id'] ?? 0)]);
            $canAccessDepartmentSwitchSidebar = (int)$stmt->fetchColumn() > 1;
        } catch (Throwable) {
            $canAccessDepartmentSwitchSidebar = false;
        }
    }
}

?>

<!-- Sidebar -->

<aside class="sidebar">

    <div class="sidebar-header">

        <h2>HMS</h2>

        <p>Hospital Management System</p>

    </div>

    <?php if ($currentUser): ?>

        <div class="sidebar-user">

            <strong>

                <?= e($currentUser['first_name']) ?>

                <?= e($currentUser['last_name']) ?>

            </strong>

            <small>

                <?= e($currentUser['role_name']) ?>

            </small>

        </div>

    <?php endif; ?>

    <nav class="sidebar-nav">

        <ul>

            <li>

                <a
                    href="<?= e($baseUrl) ?>/dashboard/index.php"
                    class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">

                    Dashboard

                </a>

            </li>

            <li>

                <a href="<?= e($baseUrl) ?>/modules/patients/search.php">

                    Patients

                </a>

            </li>

            <li>

                <a href="<?= e($baseUrl) ?>/modules/patients/search.php">

                    Encounters

                </a>

            </li>

            <li>

                <a href="<?= e($baseUrl) ?>/modules/department_notifications/index.php">

                    Notifications<?= $departmentNotificationCount > 0 ? ' (' . (int)$departmentNotificationCount . ')' : '' ?>

                </a>

            </li>

            <li>

                <a href="<?= e($baseUrl) ?>/modules/user_notifications/index.php">

                    My Notifications<?= $userNotificationCount > 0 ? ' (' . (int)$userNotificationCount . ')' : '' ?>

                </a>

            </li>

            <?php if ($canAccessMedicalRecordsSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/medical_records/index.php">

                        Medical Records

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessAccountsSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/accounts/index.php">

                        Accounts

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessStoreSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/store/index.php">

                        Store

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessAdmissionsSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/admissions/index.php">

                        Admissions

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessPharmacySidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/pharmacy/index.php">

                        Pharmacy

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessBillingSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/billing/index.php">

                        Billing

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessReportsSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/reports/index.php">

                        Reports

                    </a>

                </li>

            <?php endif; ?>

            <?php if (($currentUser['role_name'] ?? '') === 'System Administrator'): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/administration/dashboard/index.php">

                        Administration

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessDepartmentSwitchSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/administration/department_switch.php">

                        Switch Department

                    </a>

                </li>

            <?php endif; ?>

        </ul>

    </nav>

    <div class="sidebar-footer">

        <a
            href="<?= e($baseUrl) ?>/authentication/logout.php"
            class="logout-btn">

            Logout

        </a>

    </div>

</aside>
<div class="main-container">
