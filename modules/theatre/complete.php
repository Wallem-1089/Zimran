<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$recordId = (int)($_POST['id'] ?? 0);
$record = $theatreService->getById($recordId, $currentUser);

if (!$record) {
    http_response_code(404);
    exit('Theatre record not found.');
}

$result = $theatreService->complete($recordId, $currentUser);
theatreFlash($result, 'Theatre record completed.');
header('Location: view.php?id=' . $recordId);
exit;

