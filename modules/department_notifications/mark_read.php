<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/DepartmentNotificationService.php';

requireCsrfToken();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$service = new DepartmentNotificationService($pdo);
$result = $service->markRead($id, $currentUser);

$_SESSION[($result['success'] ?? false) ? 'success_message' : 'error_message'] =
    ($result['success'] ?? false)
        ? 'Notification marked as read.'
        : implode("\n", $result['errors'] ?? ['Unable to mark notification as read.']);

header('Location: index.php');
exit;
