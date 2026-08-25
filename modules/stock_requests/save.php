<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
stockRequestRequireReady($stockRequestTablesReady);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['validation_errors'] = ['Invalid CSRF token.'];
    $_SESSION['old_stock_request'] = $_POST;
    header('Location: create.php');
    exit;
}

$result = $stockRequestService->createRequest($_POST, $currentUser);
if (($result['success'] ?? false) !== true) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to create stock request.'];
    $_SESSION['old_stock_request'] = $_POST;
    header('Location: create.php');
    exit;
}

$_SESSION['success_message'] = 'Stock request submitted.';
header('Location: view.php?id=' . (int)$result['stock_request_id']);
exit;
