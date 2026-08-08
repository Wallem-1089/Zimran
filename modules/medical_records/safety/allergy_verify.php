<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();
$id = (int)($_POST['id'] ?? 0);
$allergy = $clinicalSafetyService->getAllergyById($id);
if (!$allergy) { http_response_code(404); exit('Allergy not found.'); }
if (!$permissionService->canVerifyAllergies((int)$allergy['patient_id'], $currentUser)) { clinicalSafetyAccessDenied($permissionService, $currentUser, (int)$allergy['patient_id']); }
$visitId = clinicalSafetyVisitContext($pdo, $permissionService, $currentUser, (int)$allergy['patient_id'], $_POST['visit_id'] ?? null);
$result = $clinicalSafetyService->verifyAllergy($id, (string)($_POST['reason'] ?? ''), (int)$currentUser['id'], (int)($_POST['version'] ?? 0), $visitId);
$_SESSION[$result['success'] ? 'success_message' : 'validation_errors'] = $result['success'] ? 'Allergy verified.' : $result['errors'];
header('Location: allergy_view.php?id=' . $id . clinicalSafetyQuery($visitId));
exit;
