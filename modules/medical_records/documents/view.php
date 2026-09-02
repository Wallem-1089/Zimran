<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$document = documentForUser($medicalDocumentService, (int)($_GET['id'] ?? 0), $currentUser);
$patient = $patientService->getPatientById((int)$document['patient_id']);
$visitId = documentVisitContext($pdo, $permissionService, $currentUser, (int)$document['patient_id'], $_GET['visit'] ?? $document['visit_id'] ?? null);
$canDownload = $permissionService->canDownloadMedicalDocuments((int)$document['patient_id'], $currentUser);
$canReplace = $permissionService->canReplaceMedicalDocuments((int)$document['patient_id'], $currentUser);
$canArchive = $permissionService->canArchiveMedicalDocuments((int)$document['patient_id'], $currentUser);
$canHistory = $permissionService->canViewDocumentHistory((int)$document['patient_id'], $currentUser);
$pageTitle = 'Medical Document';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
$successMessage = $_SESSION['success_message'] ?? null;
$validationErrors = $_SESSION['validation_errors'] ?? [];
unset($_SESSION['success_message'], $_SESSION['validation_errors']);
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content">
<?php if ($successMessage): ?><div class="alert-success"><?= e($successMessage) ?></div><?php endif; ?>
<?php if ($validationErrors !== []): ?><div class="alert-danger"><?= e(implode(' ', (array)$validationErrors)) ?></div><?php endif; ?>
<h1><?= e($document['title']) ?></h1>
<div class="card summary-grid">
<?php foreach (['document_type'=>'Type','document_status'=>'Status','confidentiality_level'=>'Confidentiality','current_version'=>'Current version','department_name'=>'Department','uploader_name'=>'Uploaded by','uploaded_at'=>'Uploaded','mime_type'=>'File type','file_size'=>'File size'] as $field=>$label): ?>
<div class="summary-item"><span class="summary-label"><?= e($label) ?></span> <span class="summary-value"><?= $field === 'document_type' ? e(documentTypeLabel((string)$document[$field])) : ($field === 'file_size' ? e(documentFormatBytes((int)$document[$field])) : e($document[$field] ?? 'Not recorded')) ?></span></div>
<?php endforeach; ?>
</div>
<?php if (!empty($document['description'])): ?><div class="card"><h2>Description</h2><p><?= nl2br(e($document['description'])) ?></p></div><?php endif; ?>
<div class="card"><h2>Actions</h2>
<?php if ($canDownload && $document['document_status'] !== 'Entered-in-error'): ?><a class="btn-primary" href="download.php?id=<?= (int)$document['id'] ?>">Download current version</a><?php endif; ?>
<?php if ($canReplace && $document['document_status'] === 'Active'): ?><a class="btn-secondary" href="replace.php?id=<?= (int)$document['id'] ?><?= e(documentContextQuery($visitId)) ?>">Replace</a><?php endif; ?>
<?php if ($canHistory): ?><a class="btn-secondary" href="versions.php?id=<?= (int)$document['id'] ?>">Version history</a><?php endif; ?>
<?php if ($canArchive && $document['document_status'] !== 'Entered-in-error'): ?>
<?php $actions = $document['document_status'] === 'Archived' ? ['restore'=>'Restore'] : ['archive'=>'Archive']; $actions['entered_in_error']='Entered in Error'; foreach ($actions as $action=>$label): ?>
<form class="inline-form" method="post" action="<?= e($action) ?>.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$document['id'] ?>"><input type="hidden" name="version" value="<?= (int)$document['version'] ?>"><input required name="reason" maxlength="1000" placeholder="Reason"><button class="btn-secondary" type="submit"><?= e($label) ?></button></form>
<?php endforeach; endif; ?>
</div>
<?php if ($canDownload && $document['document_status'] !== 'Entered-in-error'): ?>
<div class="card whatsapp-handoff-card">
    <div class="whatsapp-handoff-header">
        <div>
            <h2>Send Document via WhatsApp</h2>
            <p class="text-muted">Opens WhatsApp with a safe message. Download and attach the document manually; no public file link is shared.</p>
        </div>
        <span class="whatsapp-pill">Secure handoff</span>
    </div>
    <form class="whatsapp-handoff-form" method="post" action="../../patient_communications/whatsapp_handoff.php" target="_blank">
        <?= csrfField() ?>
        <input type="hidden" name="source_type" value="medical_document">
        <input type="hidden" name="source_id" value="<?= (int)$document['id'] ?>">
        <input type="hidden" name="return_url" value="../medical_records/documents/view.php?id=<?= (int)$document['id'] ?><?= e(documentContextQuery($visitId)) ?>">
        <label class="inline-check whatsapp-consent">
            <input type="checkbox" name="patient_consent_confirmed" value="1" required>
            Patient consent confirmed
        </label>
        <button class="btn-whatsapp" type="submit">Send via WhatsApp</button>
    </form>
</div>
<?php endif; ?>
<a href="../chart.php?patient=<?= (int)$patient['id'] ?>&tab=documents<?= e(documentContextQuery($visitId)) ?>">Back to Medical Documents</a>
</main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
