<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/DepartmentNotificationService.php';

requireCsrfToken();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$service = new DepartmentNotificationService($pdo);
$result = $service->resolve($id, $currentUser);

$_SESSION[($result['success'] ?? false) ? 'success_message' : 'error_message'] =
    ($result['success'] ?? false)
        ? 'Notification resolved.'
        : implode("\n", $result['errors'] ?? ['Unable to resolve notification.']);

header('Location: index.php');
exit;
