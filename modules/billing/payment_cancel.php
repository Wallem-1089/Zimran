<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

requireCsrfToken();

$paymentId = (int)($_POST['payment_id'] ?? 0);
$visitId = (int)($_POST['visit_id'] ?? 0);
$reason = trim((string)($_POST['reason'] ?? 'Cancelled by Super Administrator.'));

$result = $billingService->cancelPayment($paymentId, $reason, $currentUser);

if (!empty($result['success'])) {
    $_SESSION['success_message'] = 'Payment cancelled successfully.';
} else {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to cancel payment.'];
}

header('Location: view.php?visit=' . $visitId . '#payments');
exit;
