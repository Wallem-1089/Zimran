<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

$permissionService = new PermissionService($GLOBALS['pdo'] ?? $pdo);
$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);

$patient = $_SESSION['old_patient'] ?? [];
unset($_SESSION['old_patient']);

$registrationType = strcasecmp((string)($_GET['type'] ?? ($patient['registration_type'] ?? '')), 'Emergency') === 0
    ? 'Emergency'
    : 'Normal';

if ($registrationType === 'Emergency') {
    if (!$permissionService->canRegisterEmergencyPatient($currentUser)) {
        http_response_code(403);
        exit('You do not have permission to register emergency patients.');
    }
} elseif (!$permissionService->canRegisterPatient($currentUser)) {
    http_response_code(403);
    exit('You do not have permission to register patients.');
}

$pageTitle = 'Register Patient';
$moduleStylesheet = '/modules/patients/assets/patients.css';
$moduleScript = '/modules/patients/assets/patients.js';

$errors = $_SESSION['validation_errors'] ?? [];
unset($_SESSION['validation_errors']);

$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);

$successMessage = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

$registrationFee = $registrationType === 'Emergency' ? 50000 : 30000;
$registrationPlan = (string)($patient['registration_plan'] ?? 'Normal');
if (!in_array($registrationPlan, ['Normal', 'FamilyCard', 'ExistingFamilyCard', 'Legacy'], true)) {
    $registrationPlan = 'Normal';
}
if ($registrationType === 'Emergency') {
    $registrationPlan = 'Normal';
}
$activeFamilyCards = [];
try {
    $stmt = $pdo->query("
        SELECT id, card_number, primary_contact_name, phone, valid_until
        FROM family_cards
        WHERE status = 'Active'
          AND valid_until >= CURDATE()
        ORDER BY primary_contact_name ASC, card_number ASC
        LIMIT 500
    ");
    $activeFamilyCards = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) {
    $activeFamilyCards = [];
}

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';
?>

<div class="main-container">
<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">
    <div class="page-header">
        <div>
            <h1>Patient Registration</h1>
            <p>Start registration with basic details only. Accounts must clear the registration payment before the patient ID is generated.</p>
        </div>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e((string)$error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="save.php" class="card">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="initial_registration">
        <input type="hidden" name="registration_type" value="<?= e($registrationType) ?>">

        <div class="alert-info">
            Normal registration is <strong>&#8358;<?= e(number_format($registrationFee, 2)) ?></strong>.
            Family Card registration is <strong>&#8358;50,000.00</strong>.
            Existing active Family Card membership and Legacy Patient import do not create a new registration bill.
        </div>

        <div class="form-section">
            <h2>Initial Registration Details</h2>
            <div class="form-grid">
                <?php if ($registrationType !== 'Emergency'): ?>
                    <div class="form-group">
                        <label for="registration_plan">Registration Payment Option <span class="required">*</span></label>
                        <select id="registration_plan" name="registration_plan" required>
                            <option value="Normal" <?= $registrationPlan === 'Normal' ? 'selected' : '' ?>>Normal Registration - ₦<?= e(number_format($registrationFee, 2)) ?></option>
                            <option value="FamilyCard" <?= $registrationPlan === 'FamilyCard' ? 'selected' : '' ?>>New Family Card - ₦50,000.00</option>
                            <option value="ExistingFamilyCard" <?= $registrationPlan === 'ExistingFamilyCard' ? 'selected' : '' ?>>Add to Existing Family Card</option>
                            <option value="Legacy" <?= $registrationPlan === 'Legacy' ? 'selected' : '' ?>>Existing Hospital Patient / Legacy Patient - No initial fee</option>
                        </select>
                    </div>
                    <div class="form-group family-card-existing-fields">
                        <label for="family_card_number">Existing Family Card</label>
                        <select id="family_card_number" name="family_card_number">
                            <option value="">Select active Family Card</option>
                            <?php foreach ($activeFamilyCards as $card): ?>
                                <?php $label = (string)$card['card_number'] . ' - ' . (string)$card['primary_contact_name'] . ' (valid until ' . (string)$card['valid_until'] . ')'; ?>
                                <option value="<?= e((string)$card['card_number']) ?>" <?= (string)($patient['family_card_number'] ?? '') === (string)$card['card_number'] ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group family-card-existing-fields">
                        <label for="family_member_type">Family Member Type</label>
                        <select id="family_member_type" name="family_member_type">
                            <?php foreach (['Dependent', 'Spouse', 'Child', 'Other'] as $memberType): ?>
                                <option value="<?= e($memberType) ?>" <?= (string)($patient['family_member_type'] ?? 'Dependent') === $memberType ? 'selected' : '' ?>><?= e($memberType) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group family-card-existing-fields">
                        <label for="family_relationship">Relationship</label>
                        <input type="text" id="family_relationship" name="family_relationship" maxlength="100" value="<?= field('family_relationship', $patient) ?>" placeholder="e.g. Son, Daughter, Spouse">
                    </div>
                    <div class="form-group legacy-patient-fields">
                        <label for="legacy_hospital_number">Old Hospital/Card Number</label>
                        <input type="text" id="legacy_hospital_number" name="legacy_hospital_number" maxlength="50" value="<?= field('legacy_hospital_number', $patient) ?>">
                    </div>
                    <div class="form-group legacy-patient-fields">
                        <label for="legacy_first_seen_date">Legacy First-Seen Date</label>
                        <input type="date" id="legacy_first_seen_date" name="legacy_first_seen_date" value="<?= field('legacy_first_seen_date', $patient) ?>">
                    </div>
                    <div class="form-group legacy-patient-fields">
                        <label for="legacy_notes">Legacy Proof / Notes</label>
                        <textarea id="legacy_notes" name="legacy_notes" rows="3" placeholder="Describe old card, paper folder, or approval note"><?= field('legacy_notes', $patient) ?></textarea>
                        <small class="form-help">Enter either old hospital/card number or a note to justify importing without initial registration fee.</small>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <input type="text" id="first_name" name="first_name" maxlength="100" required value="<?= field('first_name', $patient) ?>">
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" maxlength="100" value="<?= field('middle_name', $patient) ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <input type="text" id="last_name" name="last_name" maxlength="100" required value="<?= field('last_name', $patient) ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number <span class="required">*</span></label>
                    <input type="tel" id="phone" name="phone" maxlength="20" required value="<?= field('phone', $patient) ?>">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Create Registration Billing Request</button>
            <a href="../../dashboard/index.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
    <script>
        (function () {
            const plan = document.getElementById('registration_plan');
            const fields = Array.from(document.querySelectorAll('.family-card-existing-fields'));
            const legacyFields = Array.from(document.querySelectorAll('.legacy-patient-fields'));
            function syncFamilyCardFields() {
                const show = plan && plan.value === 'ExistingFamilyCard';
                fields.forEach(function (field) {
                    field.style.display = show ? '' : 'none';
                });
                const showLegacy = plan && plan.value === 'Legacy';
                legacyFields.forEach(function (field) {
                    field.style.display = showLegacy ? '' : 'none';
                });
            }
            if (plan) {
                plan.addEventListener('change', syncFamilyCardFields);
                syncFamilyCardFields();
            }
        })();
    </script>
</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
