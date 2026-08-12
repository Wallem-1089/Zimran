<?php

declare(strict_types=1);

if (!isset($currentUser)) {
    $currentUser = null;
}

$currentPage = basename($_SERVER['PHP_SELF']);

$canAccessMedicalRecordsSidebar = false;
$departmentNotificationCount = 0;

if ($currentUser && isset($pdo)) {
    require_once __DIR__ . '/../services/PermissionService.php';
    require_once __DIR__ . '/../services/DepartmentNotificationService.php';
    $sidebarPermissionService = new PermissionService($pdo);
    $canAccessMedicalRecordsSidebar = $sidebarPermissionService->hasPermission(
        'view_medical_record',
        $currentUser
    );
    $sidebarDepartmentId = (int)(
        $currentUser['active_department_id']
        ?? $_SESSION['active_department_id']
        ?? $currentUser['department_id']
        ?? 0
    );
    if ($sidebarDepartmentId > 0) {
        $departmentNotificationCount = (new DepartmentNotificationService($pdo))->getUnreadCount($sidebarDepartmentId);
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

            <?php if ($canAccessMedicalRecordsSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/medical_records/index.php">

                        Medical Records

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($currentUser && $sidebarPermissionService->canViewBillableItems($currentUser)): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/accounts/index.php">

                        Accounts

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($currentUser && $sidebarPermissionService->canViewInventory($currentUser)): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/store/index.php">

                        Store

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($currentUser && $sidebarPermissionService->hasPermission('view_pharmacy', $currentUser)): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/pharmacy/index.php">

                        Pharmacy

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($currentUser && $sidebarPermissionService->canViewBilling($currentUser)): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/billing/index.php">

                        Billing

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($currentUser && $sidebarPermissionService->canViewReports($currentUser)): ?>

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

            <li>

                <a href="<?= e($baseUrl) ?>/modules/administration/department_switch.php">

                    Switch Department

                </a>

            </li>

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
