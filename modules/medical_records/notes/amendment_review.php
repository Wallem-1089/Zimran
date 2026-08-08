<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';requireCsrfToken();$id=(int)($_POST['amendment_id']??0);$noteId=(int)($_POST['note_id']??0);$decision=(string)($_POST['decision']??'');$result=$decision==='approve'?$clinicalNoteService->approveAmendment($id,$currentUser):$clinicalNoteService->rejectAmendment($id,(string)($_POST['reason']??''),$currentUser);noteFlash($result,$decision==='approve'?'Clinical Note amendment approved.':'Clinical Note amendment rejected.');header('Location: history.php?id='.$noteId);exit;
