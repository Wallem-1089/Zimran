<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
requireCsrfToken();

$familyCardId = (int)($_POST['family_card_id'] ?? 0);
$hospitalNumber = trim((string)($_POST['hospital_number'] ?? ''));
$memberType = trim((string)($_POST['member_type'] ?? 'Dependent'));
$relationship = trim((string)($_POST['relationship'] ?? ''));

try {
    $cardStmt = $pdo->prepare('SELECT * FROM family_cards WHERE id = :id AND status = \'Active\' LIMIT 1');
    $cardStmt->execute([':id' => $familyCardId]);
    $card = $cardStmt->fetch(PDO::FETCH_ASSOC);
    if (!$card) {
        throw new RuntimeException('Family Card is not active.');
    }

    $patientStmt = $pdo->prepare('SELECT id FROM patients WHERE hospital_number = :hospital_number LIMIT 1');
    $patientStmt->execute([':hospital_number' => $hospitalNumber]);
    $patientId = (int)($patientStmt->fetchColumn() ?: 0);
    if ($patientId <= 0) {
        throw new RuntimeException('Patient hospital number was not found.');
    }

    $allowedTypes = ['Spouse', 'Child', 'Dependent', 'Other'];
    if (!in_array($memberType, $allowedTypes, true)) {
        $memberType = 'Dependent';
    }

    $stmt = $pdo->prepare('
        INSERT INTO family_card_members (
            family_card_id, patient_id, relationship, member_type, status, added_by, added_at
        ) VALUES (
            :family_card_id, :patient_id, :relationship, :member_type, \'Active\', :added_by, NOW()
        )
        ON DUPLICATE KEY UPDATE
            family_card_id = VALUES(family_card_id),
            relationship = VALUES(relationship),
            member_type = VALUES(member_type),
            status = \'Active\',
            added_by = VALUES(added_by),
            added_at = NOW(),
            removed_by = NULL,
            removed_at = NULL
    ');
    $stmt->execute([
        ':family_card_id' => $familyCardId,
        ':patient_id' => $patientId,
        ':relationship' => $relationship === '' ? null : $relationship,
        ':member_type' => $memberType,
        ':added_by' => (int)$currentUser['id'],
    ]);

    $_SESSION['success_message'] = 'Patient added to Family Card.';
} catch (Throwable $exception) {
    $_SESSION['error_message'] = $exception->getMessage();
}

header('Location: view.php?id=' . $familyCardId);
exit;
