<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();

$itemId = (int)($_POST['id'] ?? 0);
$action = strtolower(trim((string)($_POST['action'] ?? '')));

if ($action === 'activate') {
    $result = $accountsService->activateItem($itemId, $currentUser);
    accountsFlash($result, 'Billable item activated.');
} elseif ($action === 'deactivate') {
    $result = $accountsService->deactivateItem($itemId, $currentUser);
    accountsFlash($result, 'Billable item deactivated.');
} else {
    $result = ['success' => false, 'errors' => ['Invalid billable item action.']];
    accountsFlash($result, 'Billable item status updated.');
}

header('Location: view.php?id=' . $itemId);
exit;

