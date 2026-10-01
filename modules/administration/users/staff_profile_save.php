<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$userId = (int)($_GET['id'] ?? $_POST['user_id'] ?? 0);
$targetUser = $userService->getUserById($userId);

if (!$targetUser) {
    http_response_code(404);
    exit('User not found.');
}

administrationGuardSuperAdministratorUser($targetUser, $currentUser, $permissionService);

$result = $staffProfileService->saveProfile($userId, $_POST, (int)$currentUser['id'], $_FILES);

if (!$result['success']) {
    $_SESSION['administration_errors'] = $result['errors'];
    $_SESSION['old_staff_profile'] = [
        'profile' => $_POST,
        'education' => $_POST['education'] ?? [],
        'professional' => $_POST['professional'] ?? [],
    ];
    header('Location: staff_profile.php?id=' . $userId);
    exit;
}

$_SESSION['success_message'] = 'Staff profile saved successfully.';
header('Location: view.php?id=' . $userId);
exit;
