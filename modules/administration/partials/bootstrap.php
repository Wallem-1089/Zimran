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

if (!$permissionService->isAdministrator($currentUser)) {
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
