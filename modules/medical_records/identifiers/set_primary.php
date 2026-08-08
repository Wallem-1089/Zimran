<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken(); $id=(int)($_POST['id']??0); $identifier=$identifierService->getIdentifierById($id);
if (!$identifier || !$permissionService->canManagePatientIdentifiers((int)$identifier['patient_id'], $identifier['identifier_type'], $currentUser)) { http_response_code(403); exit('Access denied.'); }
$result=$identifierService->setPrimaryIdentifier($id,(string)($_POST['reason']??''),(int)$currentUser['id']); $_SESSION[$result['success']?'success_message':'validation_errors']=$result['success']?'Primary identifier changed.':$result['errors']; header('Location: view.php?id='.$id); exit;
