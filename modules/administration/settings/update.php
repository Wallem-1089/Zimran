<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$key = trim((string)($_POST['setting_key'] ?? ''));
$result = $settingsService->update($key, $_POST['setting_value'] ?? null, $settingsActorId);
$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] = $result['success']
    ? 'Setting updated.'
    : $result['errors'];
header('Location: edit.php?key=' . urlencode($key));
exit;
