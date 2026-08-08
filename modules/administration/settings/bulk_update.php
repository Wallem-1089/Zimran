<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$group = trim((string)($_POST['group'] ?? ''));
$settings = is_array($_POST['settings'] ?? null) ? $_POST['settings'] : [];
$result = $settingsService->updateMany($settings, $settingsActorId);

$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] = $result['success']
    ? 'Settings category updated.'
    : $result['errors'];

header('Location: category.php?group=' . urlencode($group));
exit;
