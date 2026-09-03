<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$versionId = filter_input(INPUT_GET, 'version', FILTER_VALIDATE_INT) ?: null;
if (!$documentId) {
    http_response_code(404);
    exit('Medical Document not found.');
}
$result = $medicalDocumentService->prepareDownload((int)$documentId, $currentUser, $versionId);
if (!($result['success'] ?? false)) {
    http_response_code(!empty($result['audit_failed']) ? 503 : (!empty($result['forbidden']) ? 403 : 404));
    exit('The requested Medical Document is unavailable.');
}
$download = $result['data'];
$stream = $download['stream'];
$filename = preg_replace('/[\x00-\x1F\x7F"\\\\]/u', '_', (string)$download['filename']) ?: 'document';
$disposition = (string)($_GET['disposition'] ?? 'attachment');
$disposition = $disposition === 'inline' ? 'inline' : 'attachment';
header('Content-Type: ' . (string)$download['mime_type']);
header('Content-Disposition: ' . $disposition . '; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Content-Length: ' . (int)$download['file_size']);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: ' . (string)$download['cache_control']);
header('Pragma: no-cache');
header('Content-Security-Policy: sandbox');
session_write_close();
fpassthru($stream);
fclose($stream);
exit;
