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

$reason = trim((string)($_POST['reopen_reason'] ?? ''));

$visitService = new VisitService($pdo);
$result = $visitService->reopenVisit($visitId, $reason, $currentUser);

if (!($result['success'] ?? false)) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to reopen encounter.'];
    $_SESSION['old_input'] = ['reopen_reason' => $reason];
    header('Location: reopen.php?visit=' . $visitId);
    exit;
}

$_SESSION['success_message'] = 'Encounter reopened successfully.';
header('Location: workspace.php?id=' . $visitId);
exit;
