<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$visitId = (int)($_POST['visit_id'] ?? 0);
$reviewConfirmed = (string)($_POST['review_confirmed'] ?? '') === '1';

if (!$reviewConfirmed) {
    $_SESSION['old_consultation'] = $_POST;
    header('Location: create.php?visit=' . $visitId);
    exit;
}

$result = $consultationService->create($_POST, $currentUser);
consultationFlash($result, 'Consultation draft created.');

$target = ($result['success'] ?? false)
    ? 'view.php?id=' . (int)$result['consultation_id']
    : 'create.php?visit=' . $visitId;
if (!($result['success'] ?? false)) {
    $_SESSION['old_consultation'] = $_POST;
}
header('Location: ' . $target);
exit;
