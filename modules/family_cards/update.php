<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
requireCsrfToken();
$familyCardId = (int)($_POST['family_card_id'] ?? 0);
$service = new PatientRegistrationBillingService($pdo);
$result = $service->updateFamilyProfile($familyCardId, $_POST, $currentUser);
if (empty($result['success'])) {
    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to update Family Profile.'];
    header('Location: edit.php?id=' . $familyCardId);
    exit;
}
$_SESSION['success_message'] = 'Family Profile updated.';
header('Location: view.php?id=' . $familyCardId);
exit;
