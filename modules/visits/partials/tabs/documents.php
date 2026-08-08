<?php declare(strict_types=1); if (!isset($visit, $patient)) { return; } ?>
<section id="tab-documents" class="workspace-tab">
<div class="card">
    <div class="card-header"><div><h2>Medical Documents</h2><p>Authorized documents linked to this encounter.</p></div>
    <?php if ($canUploadMedicalDocuments): ?><a class="btn-primary" href="../medical_records/documents/upload.php?patient=<?= (int)$patient['id'] ?>&visit=<?= (int)$visit['id'] ?>">Upload Document</a><?php endif; ?></div>
    <div class="summary-grid"><div class="summary-item"><span class="summary-label">Encounter</span><span class="summary-value">#<?= (int)$visit['id'] ?></span></div><div class="summary-item"><span class="summary-label">Hospital Number</span><span class="summary-value"><?= e($patient['hospital_number']) ?></span></div><div class="summary-item"><span class="summary-label">Available Documents</span><span class="summary-value"><?= count($documents) ?></span></div></div>
</div>
<div class="card">
<?php if (!$canViewMedicalDocuments): ?><p>You do not have permission to view Medical Documents.</p>
<?php elseif ($documents === []): ?><p class="text-muted">No Medical Documents are linked to this encounter.</p>
<?php else: ?><div class="table-responsive"><table class="summary-table"><thead><tr><th>Document</th><th>Type</th><th>Confidentiality</th><th>Status</th><th>Version</th><th>Uploaded</th><th></th></tr></thead><tbody>
<?php foreach ($documents as $document): ?><tr><td><?= e($document['title']) ?></td><td><?= e(ucwords(str_replace('_', ' ', (string)$document['document_type']))) ?></td><td><?= e($document['confidentiality_level']) ?></td><td><?= e($document['document_status']) ?></td><td><?= (int)$document['current_version'] ?></td><td><?= e($document['uploaded_at']) ?></td><td><a href="../medical_records/documents/view.php?id=<?= (int)$document['id'] ?>&visit=<?= (int)$visit['id'] ?>">View</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<p><a href="../medical_records/chart.php?patient=<?= (int)$patient['id'] ?>&tab=documents&visit=<?= (int)$visit['id'] ?>">Open full Patient Chart document section</a></p>
</div></section>
