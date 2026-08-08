<?php declare(strict_types=1); ?>
<section class="card">
    <div class="section-heading">
        <div><h2>Medical Documents</h2><p>Protected patient-level and encounter-linked attachments.</p></div>
        <?php if ($canUploadMedicalDocuments): ?><a class="btn-primary" href="documents/upload.php?patient=<?= (int)$patient['id'] ?><?= e($chartContextQuery) ?>">Upload Document</a><?php endif; ?>
    </div>
    <?php if ($medicalDocuments === []): ?>
        <p class="text-muted">No Medical Documents are available.</p>
    <?php else: ?>
    <div class="table-responsive"><table><thead><tr><th>Title</th><th>Type</th><th>Scope</th><th>Confidentiality</th><th>Status</th><th>Version</th><th>Uploaded</th><th></th></tr></thead><tbody>
    <?php foreach ($medicalDocuments as $document): ?><tr>
        <td><?= e($document['title']) ?><?= !empty($document['confidential_hidden']) ? ' (details hidden)' : '' ?></td>
        <td><?= e(ucwords(str_replace('_', ' ', (string)$document['document_type']))) ?></td>
        <td><?= !empty($document['visit_id']) ? 'Encounter #' . (int)$document['visit_id'] : 'Patient Chart' ?></td>
        <td><?= e($document['confidentiality_level']) ?></td><td><?= e($document['document_status']) ?></td><td><?= (int)$document['current_version'] ?></td><td><?= e($document['uploaded_at']) ?></td>
        <td><a href="documents/view.php?id=<?= (int)$document['id'] ?><?= e($chartContextQuery) ?>">View</a></td>
    </tr><?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</section>
