<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php'; $note=noteForUser($clinicalNoteService,(int)($_GET['id']??0),$currentUser); $patient=$patientService->getPatientById((int)$note['patient_id']);
$visitId=noteVisitContext($pdo,$permissionService,$currentUser,(int)$note['patient_id'],$_GET['visit']??$note['visit_id']);
$own=(int)$note['author_id']===(int)$currentUser['id']; if($note['note_status']!=='Draft'||($own?!$permissionService->canEditOwnNoteDraft((int)$note['patient_id'],$currentUser):!$permissionService->canEditAnyNoteDraft((int)$note['patient_id'],$currentUser))){http_response_code(403);exit('This draft cannot be edited.');}
$noteAction='update.php';$noteSubmitLabel='Save New Draft Version';$pageTitle='Edit Clinical Note Draft';$moduleStylesheet='/modules/medical_records/assets/medical_records.css';require __DIR__.'/../../../layouts/header.php';require __DIR__.'/../../../layouts/sidebar.php';
?><div class="main-container"><?php require __DIR__.'/../../../layouts/navbar.php';?><main class="content"><h1>Edit Clinical Note Draft</h1><?php require __DIR__.'/form.php';?></main><?php require __DIR__.'/../../../layouts/footer.php';?></div>
