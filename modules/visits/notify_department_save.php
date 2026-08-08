<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/DepartmentNotificationService.php';

requireCsrfToken();

$visitId = filter_input(INPUT_POST, 'visit_id', FILTER_VALIDATE_INT) ?: 0;
$service = new DepartmentNotificationService($pdo);
$result = $service->send($_POST, $currentUser);

if ($result['success'] ?? false) {
    $_SESSION['success_message'] = 'Department notification sent.';
} else {
    $_SESSION['error_message'] = implode("\n", $result['errors'] ?? ['Unable to send department notification.']);
}

header('Location: workspace.php?id=' . $visitId);
exit;
