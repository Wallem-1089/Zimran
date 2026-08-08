<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../services/PatientService.php';
require_once __DIR__ . '/../../../services/PatientIdentifierService.php';
require_once __DIR__ . '/../../../services/PermissionService.php';

$patientService = new PatientService($pdo);
$identifierService = new PatientIdentifierService($pdo);
$permissionService = new PermissionService($pdo);
