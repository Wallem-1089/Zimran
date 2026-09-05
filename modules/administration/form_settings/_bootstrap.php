<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/../../../services/ConfigurableFormService.php';

$configurableFormService = new ConfigurableFormService($pdo, $permissionService);

if (!$permissionService->canManageConfigurableForms($currentUser)) {
    securityFailure(
        'Unauthorized configurable form settings access attempt.',
        null,
        'CONFIGURABLE_FORM_ACCESS_DENIED'
    );
}
