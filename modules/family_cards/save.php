<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: create.php'); exit; }
requireCsrfToken();

$service = new PatientRegistrationBillingService($pdo);
$result = $service->createFamilyProfile($_POST, $currentUser);
if (empty($result['success'])) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to create Family Profile.'];
    header('Location: create.php');
    exit;
}

$_SESSION['success_message'] = 'Family Profile ' . (string)$result['card_number'] . ' created. Accounts must clear ₦' . number_format((float)$result['amount'], 2) . ' before patients can be added.';
header('Location: view.php?id=' . (int)$result['family_card_id']);
exit;
