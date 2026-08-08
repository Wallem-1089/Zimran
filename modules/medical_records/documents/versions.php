<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$documentId = (int)($_GET['id'] ?? 0);
$result = $medicalDocumentService->getDocumentVersions($documentId, $currentUser);
if (!($result['success'] ?? false)) {
    http_response_code(!empty($result['audit_failed']) ? 503 : (!empty($result['forbidden']) ? 403 : 404));
    exit(!empty($result['audit_failed']) ? 'Protected document history is temporarily unavailable.' : 'Document history is unavailable.');
}
$document = $result['data']['document'];
$versions = $result['data']['versions'];
$pageTitle = 'Medical Document Versions';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content">
<h1>Document Version History</h1><p><?= e($document['title']) ?></p>
<div class="card table-responsive"><table><thead><tr><th>Version</th><th>Filename</th><th>Type</th><th>Size</th><th>Status</th><th>Scan</th><th>Uploaded by</th><th>Uploaded</th><th>Reason</th><th>Action</th></tr></thead><tbody>
<?php foreach ($versions as $version): ?><tr><td><?= (int)$version['version_number'] ?></td><td><?= e($version['original_filename']) ?></td><td><?= e($version['mime_type']) ?></td><td><?= e(documentFormatBytes((int)$version['file_size'])) ?></td><td><?= e($version['upload_status']) ?></td><td><?= e($version['malware_scan_status']) ?></td><td><?= e($version['uploader_name']) ?></td><td><?= e($version['uploaded_at']) ?></td><td><?= e($version['replacement_reason'] ?? 'Initial upload') ?></td><td><?php if ($version['upload_status'] === 'Available' && $document['document_status'] !== 'Entered-in-error'): ?><a href="download.php?id=<?= $documentId ?>&version=<?= (int)$version['id'] ?>">Download</a><?php else: ?>Unavailable<?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<a href="view.php?id=<?= $documentId ?>">Back to Document</a>
</main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
