<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

$userId = (int)($_GET['id'] ?? 0);
$user = $userService->getUserById($userId);

if (!$user) {
    http_response_code(404);
    exit('User not found.');
}

administrationGuardSuperAdministratorUser($user, $currentUser, $permissionService);

$loadedProfile = $staffProfileService->getProfile($userId);
$profile = $loadedProfile['profile'] ?? [];
$storedPath = trim((string)($profile['profile_photo_path'] ?? ''));

if ($storedPath === '') {
    http_response_code(404);
    exit('Staff profile picture not found.');
}

$projectRoot = dirname(__DIR__, 3);
$storageDir = $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'staff_profiles';
$storageRealPath = realpath($storageDir);
$filename = basename(str_replace('\\', '/', $storedPath));
$photoPath = $storageDir . DIRECTORY_SEPARATOR . $filename;
$photoRealPath = realpath($photoPath);

if ($storageRealPath === false
    || $photoRealPath === false
    || !is_file($photoRealPath)
    || !str_starts_with($photoRealPath, $storageRealPath . DIRECTORY_SEPARATOR)
) {
    http_response_code(404);
    exit('Staff profile picture not found.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string)$finfo->file($photoRealPath);
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(415);
    exit('Unsupported staff profile picture type.');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($photoRealPath));
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');
readfile($photoRealPath);
