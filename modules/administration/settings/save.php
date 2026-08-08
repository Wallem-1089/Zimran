<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$result = $settingsService->set(
    trim((string)($_POST['setting_key'] ?? '')),
    $_POST['setting_value'] ?? null,
    [
        'setting_group' => $_POST['setting_group'] ?? '',
        'setting_type' => $_POST['setting_type'] ?? 'string',
        'description' => $_POST['description'] ?? null,
        'default_value' => $_POST['default_value'] ?? null,
        'validation_rules' => $_POST['validation_rules'] ?? '{}',
        'is_public' => !empty($_POST['is_public']),
        'is_sensitive' => !empty($_POST['is_sensitive']),
        'is_editable' => true,
        'is_system' => false,
        'sort_order' => (int)($_POST['sort_order'] ?? 0)
    ],
    $settingsActorId
);

$_SESSION[$result['success'] ? 'success_message' : 'administration_errors'] = $result['success']
    ? 'Setting created.'
    : $result['errors'];

header('Location: ' . ($result['success'] ? 'edit.php?key=' . urlencode((string)$result['setting_key']) : 'create.php'));
exit;
