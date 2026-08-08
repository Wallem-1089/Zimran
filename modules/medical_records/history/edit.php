<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; $entry = longitudinalHistoryForUser($problemListService, (int)($_GET['id'] ?? 0), $currentUser);
if (!$permissionService->canManageStructuredMedicalHistory((int)$entry['patient_id'], $currentUser)) { longitudinalAccessDenied($permissionService, $currentUser, (int)$entry['patient_id'], 'MEDICAL_HISTORY_ACCESS_DENIED'); }
$patient = $patientService->getPatientById((int)$entry['patient_id']); $visitId = longitudinalVisitContext($pdo, $permissionService, $currentUser, (int)$entry['patient_id'], $_GET['visit'] ?? null);
$isCorrection = ($_GET['mode'] ?? '') === 'correct';
$historyAction = $isCorrection ? 'correct.php' : 'update.php';
$historySubmitLabel = $isCorrection ? 'Save Correction' : 'Update History Entry';
$pageTitle = $isCorrection ? 'Correct Medical History' : 'Update Medical History'; $moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php'; require __DIR__ . '/../../../layouts/sidebar.php';
?><div class="main-container"><?php require __DIR__ . '/../../../layouts/navbar.php'; ?><main class="content"><h1><?= e($pageTitle) ?></h1><?php require __DIR__ . '/form.php'; ?></main><?php require __DIR__ . '/../../../layouts/footer.php'; ?></div>
