<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../services/UserService.php';
require_once __DIR__ . '/../../../services/RoleService.php';
require_once __DIR__ . '/../../../services/PermissionService.php';
require_once __DIR__ . '/../../../services/DepartmentService.php';
require_once __DIR__ . '/../../../services/UserDepartmentService.php';

$permissionService = new PermissionService($pdo);

if (!$permissionService->isAdministrationUser($currentUser)) {
    securityFailure(
        'Unauthorized administration access attempt.',
        null,
        'ADMINISTRATION_ACCESS_DENIED'
    );
}

$userService = new UserService($pdo);
$roleService = new RoleService($pdo);
$departmentService = new DepartmentService($pdo);
$userDepartmentService = new UserDepartmentService($pdo);

function administrationGuardSuperAdministratorUser(
    array $targetUser,
    array $currentUser,
    PermissionService $permissionService
): void {
    $targetIsSuperAdministrator = ($targetUser['role_name'] ?? '') === 'Super Administrator'
        || ($targetUser['department_name'] ?? '') === 'Super Administrator';

    if ($targetIsSuperAdministrator && !$permissionService->isAdministrator($currentUser)) {
        securityFailure(
            'Unauthorized Super Administrator account access attempt.',
            null,
            'SUPER_ADMIN_USER_ACCESS_DENIED'
        );
    }
}
