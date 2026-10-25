<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
requireCsrfToken();

$familyCardId = (int)($_POST['family_card_id'] ?? 0);
$familyCardStmt = $pdo->prepare('SELECT valid_until FROM family_cards WHERE id = :id LIMIT 1');
$familyCardStmt->execute([':id' => $familyCardId]);
$familyCardValidUntil = $familyCardStmt->fetchColumn();
if (!$familyCardValidUntil || (string)$familyCardValidUntil >= date('Y-m-d')) {
    $_SESSION['error_message'] = 'Family Profile renewal can only be requested after the current coverage has expired.';
    header('Location: view.php?id=' . $familyCardId);
    exit;
}
$service = new PatientRegistrationBillingService($pdo);
$result = $service->ensureFamilyCardRenewalRequest($familyCardId, $currentUser);
if (empty($result['success'])) {
    $_SESSION['error_message'] = implode(' ', $result['errors'] ?? ['Unable to create Family Profile renewal request.']);
} elseif (!empty($result['created'])) {
    $_SESSION['success_message'] = 'Family Profile renewal billing request created and sent to Accounts.';
} else {
    $_SESSION['success_message'] = 'A pending Family Profile renewal billing request already exists.';
}
header('Location: view.php?id=' . $familyCardId);
exit;
