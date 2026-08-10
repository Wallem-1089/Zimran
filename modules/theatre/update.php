<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$recordId = (int)($_POST['id'] ?? 0);
$result = $theatreService->update($recordId, $_POST, $currentUser);
theatreFlash($result, 'Theatre record updated.');

if (($result['success'] ?? false) === true) {
    header('Location: view.php?id=' . $recordId);
    exit;
}

$visitId = (int)($_POST['visit_id'] ?? 0);
$_SESSION['old_theatre'] = $_POST;
header('Location: edit.php?id=' . $recordId);
exit;

