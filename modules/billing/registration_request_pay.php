<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registration_requests.php');
    exit;
}

requireCsrfToken();

$requestId = (int)($_POST['request_id'] ?? 0);
$notes = trim((string)($_POST['notes'] ?? ''));
$returnTo = trim((string)($_POST['return_to'] ?? ''));
$isSafeReturn = $returnTo === 'registration_requests.php'
    || preg_match('/^\.\.\/patients\/view\.php\?id=\d+$/', $returnTo) === 1;
$result = $registrationBillingService->markPaid($requestId, $currentUser, $notes);

if (empty($result['success'])) {
    $_SESSION['error_message'] = implode(' ', $result['errors'] ?? ['Unable to clear registration payment.']);
} else {
    $_SESSION['success_message'] = 'Registration payment cleared successfully.';
}

header('Location: ' . ($isSafeReturn ? $returnTo : 'registration_requests.php'));
exit;
