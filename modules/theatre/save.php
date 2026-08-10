<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$visitId = (int)($_POST['visit_id'] ?? 0);
$result = $theatreService->create($_POST, $currentUser);
theatreFlash($result, 'Theatre draft created.');

if (($result['success'] ?? false) === true) {
    header('Location: view.php?id=' . (int)$result['theatre_record_id']);
    exit;
}

$_SESSION['old_theatre'] = $_POST;
header('Location: create.php?visit=' . $visitId);
exit;

