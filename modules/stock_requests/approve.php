<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
stockRequestRequireReady($stockRequestTablesReady);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['validation_errors'] = ['Invalid request.'];
    header('Location: index.php');
    exit;
}

$requestId = (int)($_POST['id'] ?? 0);
$result = $stockRequestService->approveRequest($requestId, $currentUser);
stockRequestFlash($result, 'Stock request approved.');
header('Location: view.php?id=' . $requestId);
exit;
