<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/PatientRegistrationBillingService.php';

function assertFamilyCard(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$permissionUser = [
    'id' => 1,
    'role_id' => 0,
    'role_name' => 'Super Administrator',
    'department_name' => 'Administrator',
    'active_department_name' => 'Administrator',
];

$service = new PatientRegistrationBillingService($pdo);
$createdPatientIds = [];
$familyCardId = 0;

try {
    $result = $service->createFamilyProfile([
        'family_name' => 'Regression Family',
        'phone' => '08000000995',
        'member_capacity' => 6,
    ], $permissionUser);
    assertFamilyCard(!empty($result['success']), 'Family Profile was not created.');
    $request = $service->getRequestById((int)$result['billing_request_id']);
    assertFamilyCard($request !== null && $request['billing_type'] === 'FamilyCardActivation', 'Family Profile activation billing request is incorrect.');
    assertFamilyCard((float)$request['amount'] === 70000.00 && (int)$request['patient_id'] === 0, 'Family Profile capacity billing amount is incorrect.');
    $familyCardId = (int)($request['family_card_id'] ?? 0);
    assertFamilyCard($familyCardId > 0, 'Family Profile activation request is not linked to a family.');

    $paid = $service->markPaid((int)$result['billing_request_id'], $permissionUser, 'Regression payment');
    assertFamilyCard(!empty($paid['success']), 'Family Card activation payment failed.');

    $cardStmt = $pdo->prepare('SELECT status, valid_until FROM family_cards WHERE id = :id');
    $cardStmt->execute([':id' => $familyCardId]);
    $card = $cardStmt->fetch(PDO::FETCH_ASSOC);
    assertFamilyCard(($card['status'] ?? '') === 'Active' && !empty($card['valid_until']), 'Family Profile was not activated with validity.');

    $memberResult = $service->createNewFamilyCardMember($familyCardId, [
        'first_name' => 'Family', 'middle_name' => 'New', 'last_name' => 'Member',
        'phone' => '08000000996', 'member_type' => 'Child', 'relationship' => 'Child',
    ], $permissionUser);
    assertFamilyCard(!empty($memberResult['success']), 'New patient could not be added to the paid Family Profile.');
    $createdPatientIds[] = (int)$memberResult['patient_id'];

    $normal = $service->createInitialRegistration([
        'first_name' => 'Existing', 'last_name' => 'Patient', 'phone' => '08000000997',
        'registration_type' => 'Normal', 'registration_plan' => 'Normal',
    ], $permissionUser);
    assertFamilyCard(!empty($normal['success']), 'Existing patient fixture could not be created.');
    $createdPatientIds[] = (int)$normal['patient_id'];
    $normalPaid = $service->markPaid((int)$normal['billing_request_id'], $permissionUser, 'Fixture payment');
    assertFamilyCard(!empty($normalPaid['success']), 'Existing patient fixture could not be activated.');

    $existingResult = $service->addExistingFamilyCardMember($familyCardId, [
        'patient_id' => $createdPatientIds[1], 'member_type' => 'Dependent', 'relationship' => 'Existing patient',
    ], $permissionUser);
    assertFamilyCard(!empty($existingResult['success']), 'Existing patient could not be added to the Family Profile.');

    $pdo->prepare('UPDATE family_cards SET valid_until = DATE_SUB(CURDATE(), INTERVAL 1 DAY) WHERE id = :id')
        ->execute([':id' => $familyCardId]);
    $renewal = $service->ensureFamilyCardRenewalRequest($familyCardId, $permissionUser);
    assertFamilyCard(!empty($renewal['success']) && (float)($renewal['request']['amount'] ?? 0) === 20000.00, 'Family Card renewal request is incorrect: ' . json_encode($renewal));
    $renewed = $service->markPaid((int)$renewal['request']['id'], $permissionUser, 'Regression renewal');
    assertFamilyCard(!empty($renewed['success']), 'Family Card renewal payment failed.');
    $pdo->prepare('UPDATE family_cards SET valid_until = DATE_SUB(CURDATE(), INTERVAL 366 DAY) WHERE id = :id')
        ->execute([':id' => $familyCardId]);
    $lapsedRenewal = $service->ensureFamilyCardRenewalRequest($familyCardId, $permissionUser);
    assertFamilyCard(!empty($lapsedRenewal['success']) && (float)($lapsedRenewal['request']['amount'] ?? 0) === 30000.00, 'Family Profile renewal after 365 inactive days should be 30000.');

    echo "PASS: Family Card regression passed.\n";
} finally {
    if ($familyCardId > 0) {
        $deleteRequests = $pdo->prepare('DELETE FROM patient_registration_billing_requests WHERE family_card_id = :family_card_id');
        $deleteRequests->execute([':family_card_id' => $familyCardId]);
        $deleteMembers = $pdo->prepare('DELETE FROM family_card_members WHERE family_card_id = :family_card_id');
        $deleteMembers->execute([':family_card_id' => $familyCardId]);
        $deleteCard = $pdo->prepare('DELETE FROM family_cards WHERE id = :id');
        $deleteCard->execute([':id' => $familyCardId]);
    }
    if ($createdPatientIds !== []) {
        $placeholders = implode(',', array_fill(0, count($createdPatientIds), '?'));
        $deleteRequests = $pdo->prepare('DELETE FROM patient_registration_billing_requests WHERE patient_id IN (' . $placeholders . ')');
        $deleteRequests->execute($createdPatientIds);
        $deletePatients = $pdo->prepare('DELETE FROM patients WHERE id IN (' . $placeholders . ')');
        $deletePatients->execute($createdPatientIds);
    }
}
