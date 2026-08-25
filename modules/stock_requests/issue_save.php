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
$result = $stockRequestService->issueRequest($requestId, (array)($_POST['issue_quantity'] ?? []), $currentUser);
stockRequestFlash($result, 'Stock request issued.');
header('Location: view.php?id=' . $requestId);
exit;
