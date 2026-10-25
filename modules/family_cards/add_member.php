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
    $patientStmt = $pdo->prepare('SELECT id FROM patients WHERE hospital_number = :hospital_number LIMIT 1');
    $patientStmt->execute([':hospital_number' => $hospitalNumber]);
    $patientId = (int)($patientStmt->fetchColumn() ?: 0);
    if ($patientId <= 0) {
        throw new RuntimeException('Patient hospital number was not found.');
    }

    $service = new PatientRegistrationBillingService($pdo);
    $result = $service->addExistingFamilyCardMember($familyCardId, [
        'patient_id' => $patientId,
        'member_type' => $memberType,
        'relationship' => $relationship,
    ], $currentUser);
    if (empty($result['success'])) {
        throw new RuntimeException(implode(' ', $result['errors'] ?? ['Unable to add patient to Family Profile.']));
    }

    $_SESSION['success_message'] = 'Patient added to Family Profile.';
} catch (Throwable $exception) {
    $_SESSION['error_message'] = $exception->getMessage();
}

header('Location: view.php?id=' . $familyCardId);
exit;
