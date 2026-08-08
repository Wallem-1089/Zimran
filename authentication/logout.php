<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/SessionService.php';

$sessionService = new SessionService($pdo);
$sessionService->logout();

header('Location: login.php');
exit;
