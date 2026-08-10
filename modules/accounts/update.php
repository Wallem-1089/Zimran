<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$itemId = (int)($_POST['id'] ?? 0);
$result = $accountsService->updateItem($itemId, $_POST, $currentUser);
accountsFlash($result, 'Billable item updated.');

if (($result['success'] ?? false) === true) {
    header('Location: view.php?id=' . $itemId);
    exit;
}

$_SESSION['old_billable_item'] = $_POST;
header('Location: edit.php?id=' . $itemId);
exit;

