<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$consultationId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$result = $consultationService->complete($consultationId, $currentUser);

if ($result['success'] ?? false) {
    $_SESSION['success_message'] = 'Consultation completed.';
    header('Location: view.php?id=' . (int)$result['consultation_id']);
    exit;
}

$_SESSION['error_message'] = implode("\n", $result['errors'] ?? ['Unable to complete consultation.']);
header('Location: view.php?id=' . $consultationId);
exit;
