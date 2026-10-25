<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
requireCsrfToken();

$familyCardId = (int)($_POST['family_card_id'] ?? 0);
$service = new PatientRegistrationBillingService($pdo);
$result = $service->createNewFamilyCardMember($familyCardId, $_POST, $currentUser);
if (!empty($result['success'])) {
    $_SESSION['success_message'] = 'New patient created and added to the Family Profile.';
} else {
    $_SESSION['error_message'] = implode(' ', $result['errors'] ?? ['Unable to create patient under Family Profile.']);
}
header('Location: view.php?id=' . $familyCardId);
exit;
