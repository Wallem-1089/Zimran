<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$consultationId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$result = $consultationService->update($consultationId, $_POST, $currentUser);

if ($result['success'] ?? false) {
    $_SESSION['success_message'] = 'Consultation updated.';
    header('Location: view.php?id=' . (int)$result['consultation_id']);
    exit;
}

$_SESSION['error_message'] = implode("\n", $result['errors'] ?? ['Unable to update consultation.']);
header('Location: edit.php?id=' . $consultationId);
exit;
