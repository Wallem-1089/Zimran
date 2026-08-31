<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../services/SessionService.php';
require_once __DIR__ . '/../../../services/UserService.php';
require_once __DIR__ . '/../../../services/AuditService.php';
require_once __DIR__ . '/../../../services/PermissionService.php';

$securitySessionService = new SessionService($pdo);
$securityConfig = require __DIR__ . '/../../../config/app.php';

if (($securityConfig['app']['environment'] ?? 'production') !== 'development') {
    $securitySessionService->requireAuthentication();
}
$securityUserService = new UserService($pdo);
$securityAuditService = new AuditService($pdo);
$securityPermissionService = new PermissionService($pdo);
$isSecurityAdministrator = $securityPermissionService->isAdministrationUser($currentUser);

function requireSecurityAdministrator(): void
{
    global $isSecurityAdministrator;

    if (!$isSecurityAdministrator) {
        securityFailure(
            'Unauthorized security administration access attempt.',
            null,
            'SECURITY_ADMINISTRATION_DENIED'
        );
    }
}
