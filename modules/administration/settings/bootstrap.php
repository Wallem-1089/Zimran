<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/../../../services/SettingsService.php';

if (!$permissionService->canManageSettings($currentUser)) {
    securityFailure(
        'Unauthorized system settings access attempt.',
        null,
        'SETTINGS_ACCESS_DENIED'
    );
}

$settingsService = new SettingsService($pdo);
$settingsActorId = (int)($currentUser['id'] ?? 0);
