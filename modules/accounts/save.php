<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

if (!$accountsTablesReady) {
    http_response_code(503);
    exit('Accounts tables are not available yet. Apply Migration 030 to enable this section.');
}

$result = $accountsService->createItem($_POST, $currentUser);
accountsFlash($result, 'Billable item created.');

if (($result['success'] ?? false) === true) {
    header('Location: view.php?id=' . (int)$result['billable_item_id']);
    exit;
}

$_SESSION['old_billable_item'] = $_POST;
header('Location: create.php');
exit;

