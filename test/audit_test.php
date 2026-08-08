<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/AuditService.php';

$audit = new AuditService($pdo);

$result = $audit->log(
    1,
    null,
    'Testing',
    'System Test',
    'Audit service is working.'
);

echo $result
    ? 'Audit Logged Successfully'
    : 'Audit Failed';
