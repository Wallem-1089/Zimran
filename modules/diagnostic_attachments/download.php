<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/DiagnosticAttachmentService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

$attachmentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($attachmentId <= 0) {
    http_response_code(400);
    exit('Invalid attachment.');
}

$service = new DiagnosticAttachmentService($pdo, new PermissionService($pdo));
$download = $service->getForDownload($attachmentId, $currentUser ?? ($_SESSION['user'] ?? []));

if (empty($download['success'])) {
    http_response_code(404);
    exit(implode(' ', $download['errors'] ?? ['Attachment not found.']));
}

$path = (string)$download['path'];
$filename = basename((string)$download['filename']);
$mime = (string)($download['mime_type'] ?? 'application/octet-stream');

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($path));
header('Content-Disposition: inline; filename="' . str_replace('"', '', $filename) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
