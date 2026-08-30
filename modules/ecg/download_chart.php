<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$requestId) {
    http_response_code(400);
    exit('Invalid ECG request.');
}

$download = $ecgService->prepareChartDownload($requestId, $currentUser);
if (!($download['success'] ?? false)) {
    http_response_code(404);
    exit(e((string)(($download['errors'][0] ?? 'ECG chart not found.'))));
}

$path = (string)$download['path'];
$filename = basename((string)$download['filename']);
$mimeType = (string)($download['mime_type'] ?? 'application/octet-stream');

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string)filesize($path));
header('Content-Disposition: inline; filename="' . str_replace('"', '', $filename) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;

