<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php'; requireCsrfToken();
$patientId=(int)($_POST['patient_id']??0); $visitId=noteVisitContext($pdo,$permissionService,$currentUser,$patientId,$_POST['visit_id']??null); $_POST['visit_id']=$visitId;
$result=$clinicalNoteService->createDraft($_POST,$currentUser); noteFlash($result,'Clinical Note draft created.');
$target=($result['success']??false)?'view.php?id='.(int)$result['note_id']:'create.php?patient='.$patientId;
header('Location: '.$target.noteContextQuery($visitId)); exit;
