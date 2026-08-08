<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php'; requireCsrfToken();
$id=(int)($_POST['id']??0); $metadata=$clinicalNoteService->getNoteById($id); if(!$metadata){http_response_code(404);exit('Clinical Note not found.');}
$visitId=noteVisitContext($pdo,$permissionService,$currentUser,(int)$metadata['patient_id'],$_POST['visit_id']??$metadata['visit_id']);
$result=$clinicalNoteService->updateDraft($id,$_POST,(int)($_POST['version']??0),$currentUser); noteFlash($result,'Clinical Note draft updated.');
header('Location: '.(($result['success']??false)?'view.php?id='.$id:'edit.php?id='.$id).noteContextQuery($visitId));exit;
