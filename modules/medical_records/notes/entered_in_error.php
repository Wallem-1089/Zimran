<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';requireCsrfToken();$id=(int)($_POST['id']??0);$visitId=filter_var($_POST['visit_id']??null,FILTER_VALIDATE_INT)?:null;$result=$clinicalNoteService->markNoteEnteredInError($id,(string)($_POST['reason']??''),(int)($_POST['version']??0),$currentUser,$visitId);noteFlash($result,'Clinical Note marked entered in error.');header('Location: view.php?id='.$id.noteContextQuery($visitId));exit;
