<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$problem = longitudinalProblemForUser($problemListService, (int)($_GET['id'] ?? 0), $currentUser);
if (!$permissionService->canManageProblemList((int)$problem['patient_id'], $currentUser)) { longitudinalAccessDenied($permissionService, $currentUser, (int)$problem['patient_id'], 'PROBLEM_LIST_ACCESS_DENIED'); }
$patient = $patientService->getPatientById((int)$problem['patient_id']);
$visitId = longitudinalVisitContext($pdo, $permissionService, $currentUser, (int)$problem['patient_id'], $_GET['visit'] ?? null);
$problemAction = 'update.php'; $problemSubmitLabel = 'Update Problem'; $pageTitle = 'Update Problem';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php'; require __DIR__ . '/../../../layouts/sidebar.php';
?><div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content"><h1>Update Problem</h1><?php require __DIR__ . '/form.php'; ?></main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
