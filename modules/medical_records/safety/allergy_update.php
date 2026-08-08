<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();
$id = (int)($_POST['id'] ?? 0);
$allergy = $clinicalSafetyService->getAllergyById($id);
if (!$allergy) { http_response_code(404); exit('Allergy not found.'); }
if (!$permissionService->canUpdateAllergies((int)$allergy['patient_id'], $currentUser)) { clinicalSafetyAccessDenied($permissionService, $currentUser, (int)$allergy['patient_id']); }
$visitId = clinicalSafetyVisitContext($pdo, $permissionService, $currentUser, (int)$allergy['patient_id'], $_POST['visit_id'] ?? null);
$_POST['visit_id'] = $visitId;
$result = $clinicalSafetyService->updateAllergy($id, $_POST, (int)($_POST['version'] ?? 0), (int)$currentUser['id']);
$_SESSION[$result['success'] ? 'success_message' : 'validation_errors'] = $result['success'] ? 'Allergy updated.' : $result['errors'];
$contextQuery = clinicalSafetyQuery($visitId);
header('Location: ' . ($result['success'] ? 'allergy_view.php?id=' . $id . $contextQuery : 'allergy_edit.php?id=' . $id . $contextQuery));
exit;
