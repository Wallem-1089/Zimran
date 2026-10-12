<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
requireCsrfToken();

$familyCardId = (int)($_POST['family_card_id'] ?? 0);
$memberId = (int)($_POST['member_id'] ?? 0);

try {
    $stmt = $pdo->prepare('
        UPDATE family_card_members
        SET status = \'Removed\',
            removed_by = :removed_by,
            removed_at = NOW()
        WHERE id = :id
          AND family_card_id = :family_card_id
          AND member_type <> \'Principal\'
    ');
    $stmt->execute([
        ':removed_by' => (int)$currentUser['id'],
        ':id' => $memberId,
        ':family_card_id' => $familyCardId,
    ]);

    $_SESSION['success_message'] = $stmt->rowCount() > 0
        ? 'Family Card member removed.'
        : 'No removable Family Card member was found.';
} catch (Throwable) {
    $_SESSION['error_message'] = 'Unable to remove Family Card member.';
}

header('Location: view.php?id=' . $familyCardId);
exit;
