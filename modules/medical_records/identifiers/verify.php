<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken(); $id=(int)($_POST['id']??0);
if (!$permissionService->canVerifyPatientIdentifiers($currentUser)) { http_response_code(403); exit('Access denied.'); }
$result=$identifierService->verifyIdentifier($id,(string)($_POST['reason']??''),(int)$currentUser['id']); $_SESSION[$result['success']?'success_message':'validation_errors']=$result['success']?'Identifier verified.':$result['errors']; header('Location: view.php?id='.$id); exit;
