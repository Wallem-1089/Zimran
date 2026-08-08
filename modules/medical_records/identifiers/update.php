<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken();
$identifier = $identifierService->getIdentifierById((int)($_POST['identifier_id'] ?? 0));
if (!$identifier || !$permissionService->canManagePatientIdentifiers((int)$identifier['patient_id'], (string)($_POST['identifier_type'] ?? ''), $currentUser)) { http_response_code(403); exit('Access denied.'); }
$result = $identifierService->updateIdentifier((int)$identifier['id'], $_POST, (int)($_POST['version'] ?? 0), (int)$currentUser['id']);
$_SESSION[$result['success'] ? 'success_message' : 'validation_errors'] = $result['success'] ? 'Identifier updated.' : $result['errors'];
header('Location: ' . ($result['success'] ? 'view.php?id=' : 'edit.php?id=') . (int)$identifier['id']); exit;
