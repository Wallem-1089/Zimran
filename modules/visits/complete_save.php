<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/VisitService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../patients/search.php');
    exit;
}

requireCsrfToken();

$visitId = filter_input(INPUT_POST, 'visit_id', FILTER_VALIDATE_INT) ?: 0;
if ($visitId <= 0) {
    $_SESSION['error_message'] = 'Invalid encounter selected.';
    header('Location: ../patients/search.php');
    exit;
}

$data = [
    'discharge_diagnosis' => trim((string)($_POST['discharge_diagnosis'] ?? '')),
    'discharge_notes' => trim((string)($_POST['discharge_notes'] ?? '')),
    'follow_up_instructions' => trim((string)($_POST['follow_up_instructions'] ?? '')),
];

$visitService = new VisitService($pdo);
$result = $visitService->completeVisitWithDischarge($visitId, $data, $currentUser);

if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to complete encounter.'];
    $_SESSION['old_input'] = $data;
    header('Location: complete.php?visit=' . $visitId);
    exit;
}

$_SESSION['success_message'] = 'Encounter completed successfully.';
header('Location: workspace.php?id=' . $visitId);
exit;
