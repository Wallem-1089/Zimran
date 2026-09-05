<?php

declare(strict_types=1);

if (!isset($currentUser)) {
    $currentUser = null;
}

$currentPage = basename($_SERVER['PHP_SELF']);

$canAccessMedicalRecordsSidebar = false;
$canAccessLaboratorySidebar = false;
$canAccessRadiologySidebar = false;
$canAccessEcgSidebar = false;
$canAccessPopSidebar = false;
$canAccessPhysiotherapySidebar = false;
$canAccessTheatreSidebar = false;
$canAccessAccountsSidebar = false;
$canAccessStoreSidebar = false;
$canAccessStockRequestsSidebar = false;
$canAccessAdmissionsSidebar = false;
$canAccessPharmacySidebar = false;
$canAccessBillingSidebar = false;
$canAccessReportsSidebar = false;
$canAccessDepartmentSwitchSidebar = false;
$canAccessDepartmentWorklistSidebar = false;
$sidebarStockRequestOnly = false;
$departmentWorklistCount = 0;
$departmentNotificationCount = 0;
$userNotificationCount = 0;

if ($currentUser && isset($pdo)) {
    require_once __DIR__ . '/../services/PermissionService.php';
    require_once __DIR__ . '/../services/DepartmentNotificationService.php';
    require_once __DIR__ . '/../services/UserNotificationService.php';
    require_once __DIR__ . '/../services/VisitService.php';
    $sidebarPermissionService = new PermissionService($pdo);
    $sidebarRole = (string)($currentUser['role_name'] ?? '');
    $sidebarDepartment = (string)(
        $currentUser['active_department_name']
        ?? $_SESSION['active_department_name']
        ?? $currentUser['department_name']
        ?? ''
    );
    $sidebarIsAdmin = $sidebarPermissionService->isAdministrator($currentUser);
    $sidebarIsAdministrationUser = $sidebarPermissionService->isAdministrationUser($currentUser);
    $sidebarStockRequestOnly = !$sidebarIsAdmin
        && (
            $sidebarRole === 'Orderly'
            || $sidebarDepartment === 'Orderly'
        );
    $sidebarRoleIn = static fn(array $roles): bool => in_array(
        $sidebarRole,
        $roles,
        true
    );
    $sidebarDepartmentIn = static fn(array $departments): bool => in_array(
        $sidebarDepartment,
        $departments,
        true
    );
    $sidebarCan = static function (string $permission) use (
        $sidebarPermissionService,
        $currentUser
    ): bool {
        try {
            return $sidebarPermissionService->hasPermission(
                $permission,
                $currentUser
            );
        } catch (Throwable) {
            return false;
        }
    };
    $sidebarOwnsDepartmentModule = static function (
        array $roles,
        array $departments
    ) use (
        $sidebarIsAdmin,
        $sidebarRoleIn,
        $sidebarDepartmentIn
    ): bool {
        return $sidebarIsAdmin
            || $sidebarRoleIn($roles)
            || $sidebarDepartmentIn($departments);
    };

    /*
    |--------------------------------------------------------------------------
    | Sidebar Visibility
    |--------------------------------------------------------------------------
    |
    | Patient-specific cross-view permissions are intentionally broader inside
    | the Encounter Workspace. Sidebar module links expose department-wide
    | worklists/master-data screens, so they stay department-owned.
    |
    */

    $canAccessMedicalRecordsSidebar = $sidebarCan('view_medical_record')
        && $sidebarOwnsDepartmentModule(['Records Officer'], ['Records']);

    $canAccessLaboratorySidebar = $sidebarCan('view_laboratory')
        && $sidebarOwnsDepartmentModule(['Laboratory Scientist'], ['Laboratory']);

    $canAccessRadiologySidebar = $sidebarCan('view_radiology')
        && $sidebarOwnsDepartmentModule(['Radiographer'], ['X-Ray', 'Radiology']);

    $canAccessEcgSidebar = $sidebarCan('view_ecg')
        && $sidebarOwnsDepartmentModule(['ECG Technician'], ['ECG']);

    $canAccessPopSidebar = $sidebarCan('view_pop')
        && $sidebarOwnsDepartmentModule(['POP Technician'], ['POP']);

    $canAccessPhysiotherapySidebar = $sidebarCan('view_physiotherapy')
        && $sidebarOwnsDepartmentModule(['Physiotherapist'], ['Physiotherapy', 'Physio', 'Rehabilitation']);

    $canAccessTheatreSidebar = $sidebarCan('view_theatre')
        && $sidebarOwnsDepartmentModule(['Theatre Staff', 'Doctor'], ['Theatre', 'Doctor']);

    $canAccessAccountsSidebar = $sidebarCan('view_billable_items')
        && $sidebarOwnsDepartmentModule(['Accountant', 'Accounts'], ['Accounts']);

    $canAccessStoreSidebar = $sidebarCan('view_inventory')
        && $sidebarOwnsDepartmentModule(['Store Officer'], ['Store']);

    $canAccessStockRequestsSidebar = (
        $sidebarCan('view_stock_requests')
        || $sidebarCan('create_stock_request')
    ) && $sidebarOwnsDepartmentModule(
        [
            'Doctor',
            'Nurse',
            'Laboratory Scientist',
            'Radiographer',
            'ECG Technician',
            'POP Technician',
            'Physiotherapist',
            'Theatre Staff',
            'Pharmacist',
            'Store Officer',
            'Orderly',
        ],
        [
            'Doctor',
            'Nursing',
            'Laboratory',
            'Radiology',
            'X-Ray',
            'ECG',
            'POP',
            'Physiotherapy',
            'Physio',
            'Rehabilitation',
            'Theatre',
            'Pharmacy',
            'Store',
            'Orderly',
        ]
    );

    $canAccessAdmissionsSidebar = $sidebarCan('view_admissions')
        && $sidebarOwnsDepartmentModule(
            ['Receptionist', 'Records Officer', 'Doctor', 'Nurse'],
            ['Reception', 'Records', 'Doctor', 'Nursing']
        );

    $canAccessPharmacySidebar = $sidebarCan('view_pharmacy')
        && $sidebarOwnsDepartmentModule(['Pharmacist'], ['Pharmacy']);

    $canAccessBillingSidebar = $sidebarCan('view_billing')
        && $sidebarOwnsDepartmentModule(['Accountant', 'Accounts'], ['Accounts']);

    $canAccessReportsSidebar = $sidebarIsAdmin
        || (
            $sidebarDepartmentIn(['Accounts'])
            && (
                $sidebarCan('view_reports')
                || $sidebarCan('view_financial_reports')
            )
        )
        || (
            $sidebarDepartmentIn(['Store'])
            && (
                $sidebarCan('view_reports')
                || $sidebarCan('view_inventory_reports')
            )
        )
        || (
            $sidebarDepartmentIn(['Records'])
            && (
                $sidebarCan('view_reports')
                || $sidebarCan('view_clinical_reports')
            )
        );

    $sidebarDepartmentId = (int)(
        $currentUser['active_department_id']
        ?? $_SESSION['active_department_id']
        ?? $currentUser['department_id']
        ?? 0
    );
    $sidebarOwnsEncounterWorklist = $sidebarOwnsDepartmentModule(
        [
            'System Administrator',
            'Receptionist',
            'Records Officer',
            'Doctor',
            'Nurse',
            'Laboratory Scientist',
            'Radiographer',
            'ECG Technician',
            'POP Technician',
            'Physiotherapist',
            'Theatre Staff',
            'Pharmacist',
            'Accountant',
            'Accounts',
        ],
        [
            'Administrator',
            'Reception',
            'Records',
            'Doctor',
            'Nursing',
            'Laboratory',
            'Radiology',
            'X-Ray',
            'ECG',
            'POP',
            'Physiotherapy',
            'Physio',
            'Rehabilitation',
            'Theatre',
            'Pharmacy',
            'Accounts',
        ]
    );
    if ($sidebarDepartmentId > 0) {
        try {
            if ($sidebarPermissionService->hasPermission('view_encounter', $currentUser)
                && $sidebarOwnsEncounterWorklist
                && (
                    $sidebarIsAdmin
                    || $sidebarPermissionService->canViewAllDepartmentWorklists($currentUser)
                    || $sidebarPermissionService->canAccessDepartment($sidebarDepartmentId, $currentUser)
                )
            ) {
                $canAccessDepartmentWorklistSidebar = true;
                $departmentWorklistCount = count((new VisitService($pdo))->listDepartmentWorklist($sidebarDepartmentId));
            }
        } catch (Throwable) {
            $departmentWorklistCount = 0;
        }

        try {
            $departmentNotificationCount = (new DepartmentNotificationService($pdo))->getUnreadCount($sidebarDepartmentId);
        } catch (Throwable) {
            $departmentNotificationCount = 0;
        }

        if ($sidebarDepartmentIn(['Accounts'])
            && $sidebarPermissionService->canViewBillingRequests($currentUser)
        ) {
            $canAccessDepartmentWorklistSidebar = true;
            try {
                $stmt = $pdo->prepare(
                    'SELECT COUNT(*)
                     FROM information_schema.tables
                     WHERE table_schema = DATABASE()
                       AND table_name = :table'
                );
                $stmt->execute([':table' => 'billing_requests']);
                if ((int)$stmt->fetchColumn() > 0) {
                    $pendingBillingRequests = $pdo->query(
                        "SELECT COUNT(*) FROM billing_requests WHERE status = 'Pending'"
                    )->fetchColumn();
                    $departmentWorklistCount += (int)$pendingBillingRequests;
                }
            } catch (Throwable) {
                // Keep the sidebar usable even if the optional Billing Request
                // table is not present yet.
            }
        }
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

$sidebarBranding = appBranding($pdo ?? null);

?>

<!-- Sidebar -->

<aside class="sidebar">

    <div class="sidebar-header">

        <h2><?= e($sidebarBranding['hospital_code']) ?></h2>

        <p><?= e($sidebarBranding['product_name']) ?></p>

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

            <?php if (!$sidebarStockRequestOnly): ?>

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

            <?php if ($canAccessDepartmentWorklistSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/visits/department_worklist.php">

                        Department Worklist<?= $departmentWorklistCount > 0 ? ' (' . (int)$departmentWorklistCount . ')' : '' ?>

                    </a>

                </li>

            <?php endif; ?>

            <li>

                <a href="<?= e($baseUrl) ?>/modules/department_notifications/index.php">

                    Department Notifications<?= $departmentNotificationCount > 0 ? ' (' . (int)$departmentNotificationCount . ')' : '' ?>

                </a>

            </li>

            <li>

                <a href="<?= e($baseUrl) ?>/modules/user_notifications/index.php">

                    My Notifications<?= $userNotificationCount > 0 ? ' (' . (int)$userNotificationCount . ')' : '' ?>

                </a>

            </li>

            <?php endif; ?>

            <?php if ($canAccessMedicalRecordsSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/medical_records/index.php">

                        Medical Records

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessLaboratorySidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/laboratory/index.php">

                        Laboratory

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessRadiologySidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/radiology/index.php">

                        Radiology

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessEcgSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/ecg/index.php">

                        ECG

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessPopSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/pop/index.php">

                        POP

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessPhysiotherapySidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/physiotherapy/index.php">

                        Physiotherapy

                    </a>

                </li>

            <?php endif; ?>

            <?php if ($canAccessTheatreSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/theatre/index.php">

                        Theatre

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

            <?php if ($canAccessStockRequestsSidebar): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/stock_requests/index.php">

                        Stock Requests

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

            <?php if (($currentUser['role_name'] ?? '') === 'System Administrator' || ($currentUser['role_name'] ?? '') === 'Super Administrator'): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/administration/dashboard/index.php">

                        Administration

                    </a>

                </li>

            <?php endif; ?>

            <?php if (isset($sidebarPermissionService) && $sidebarPermissionService->canManageConfigurableForms($currentUser)): ?>

                <li>

                    <a href="<?= e($baseUrl) ?>/modules/administration/form_settings/index.php">

                        Form Settings

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
