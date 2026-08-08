<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php'; requireCsrfToken();
$id=(int)($_POST['id']??0);$result=$clinicalNoteService->signNote($id,(int)($_POST['version']??0),$currentUser,filter_var($_POST['visit_id']??null,FILTER_VALIDATE_INT)?:null);noteFlash($result,'Clinical Note signed and locked.');header('Location: view.php?id='.$id.noteContextQuery(filter_var($_POST['visit_id']??null,FILTER_VALIDATE_INT)?:null));exit;
