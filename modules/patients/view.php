<?php

declare(strict_types=1);

$pageTitle = 'Patient Profile';
$moduleStylesheet = '/modules/patients/assets/patients.css';

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/PatientRegistrationBillingService.php';

$id = filter_input(

    INPUT_GET,

    'id',

    FILTER_VALIDATE_INT

);

if (!$id) {

    header('Location: search.php');

    exit;

}

$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$registrationBillingService = new PatientRegistrationBillingService($pdo);

$patient = $patientService->getPatientById($id);

if (!$patient) {

    http_response_code(404);

    exit('Patient not found.');

}

$canViewMedicalRecord = $permissionService->canViewMedicalRecord(
    $id,
    $currentUser
);
$canViewBilling = $permissionService->canViewBilling($currentUser);
$canViewLaboratory = $permissionService->canViewLaboratory($id, $currentUser);
$canDeletePatient = $permissionService->canDeletePatient(
    $id,
    $currentUser
);
$isDeletedPatient = (int)($patient['is_deleted'] ?? 0) === 1;
$registrationGate = $patientService->getRegistrationGateStatus($id);
$canRequestRegistrationRenewal = !$isDeletedPatient
    && (
        $permissionService->canRegisterPatient($currentUser)
        || $permissionService->canCreateBillingRequest($currentUser)
        || $permissionService->canReviewBillingRequest($currentUser)
        || $permissionService->canRecordPayment($currentUser)
    );
$canViewRegistrationBillingActions = $canViewBilling
    || $canRequestRegistrationRenewal
    || $permissionService->canViewBillingRequests($currentUser);
$canClearRegistrationPayment = $permissionService->canReviewBillingRequest($currentUser)
    || $permissionService->canRecordPayment($currentUser);
$registrationBillingHistory = $canViewRegistrationBillingActions
    ? $registrationBillingService->listRequests(['patient_id' => $id, 'status' => ''])
    : [];
$pendingRenewalRequest = null;
foreach ($registrationBillingHistory as $registrationRequest) {
    if ((string)($registrationRequest['billing_type'] ?? '') === 'MonthlyRenewal'
        && (string)($registrationRequest['status'] ?? '') === 'Pending'
    ) {
        $pendingRenewalRequest = $registrationRequest;
        break;
    }
}

if ($isDeletedPatient && !$permissionService->canViewDeletedPatient($currentUser)) {
    http_response_code(404);
    exit('Patient not found.');
}

if ($isDeletedPatient) {
    $canViewMedicalRecord = false;
    $canViewBilling = false;
    $canViewLaboratory = false;
}

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

?>

<div class="main-container">

<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">

<div class="page-header">

    <div>

        <h1>

            Patient Profile

        </h1>

        <p>

            Patient information and registration details.

        </p>

    </div>

    <div class="form-actions">

        <a class="btn-secondary" href="print_face_sheet.php?id=<?= (int)$patient['id'] ?>">Print Patient Face Sheet</a>

    </div>

</div>

<?php if (isset($_SESSION['success_message'])) : ?>

<div class="alert-success">

    <?= e($_SESSION['success_message']) ?>

</div>

<?php unset($_SESSION['success_message']); ?>

<?php endif; ?>

<?php if (isset($_SESSION['error_message'])) : ?>

<div class="alert-danger">

    <?= e($_SESSION['error_message']) ?>

</div>

<?php unset($_SESSION['error_message']); ?>

<?php endif; ?>

<?php if ($isDeletedPatient) : ?>

<div class="alert-warning">

    <strong>Deleted Patient Record</strong>
    This patient has been soft-deleted/voided and is retained only for audit and
    historical record integrity. New encounters and normal chart access are disabled.

</div>

<?php endif; ?>

<?php if (!$isDeletedPatient && empty($registrationGate['can_create_encounter'])) : ?>

<div class="alert-warning">
    <strong>Registration Billing Required</strong>
    <?= e((string)$registrationGate['message']) ?>
    <?php if (($registrationGate['status'] ?? '') === 'Expired' && $canRequestRegistrationRenewal): ?>
        <?php if ($pendingRenewalRequest && $permissionService->canViewBillingRequests($currentUser)): ?>
            <p style="margin-top: 1rem;">
                <a class="btn-primary" href="../billing/registration_requests.php?<?= e(http_build_query([
                    'status' => 'Pending',
                    'patient_name' => trim((string)($patient['first_name'] ?? '') . ' ' . (string)($patient['last_name'] ?? '')),
                    'hospital_number' => (string)($patient['hospital_number'] ?? ''),
                ])) ?>">Open Pending Renewal Payment</a>
            </p>
        <?php elseif (!$pendingRenewalRequest): ?>
            <form method="post" action="renew_registration.php" class="inline-form" style="margin-top: 1rem;">
                <?= csrfField() ?>
                <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
                <button class="btn-primary" type="submit">Create Renewal Payment Request</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php if (isset($_SESSION['validation_errors'])) : ?>

<div class="alert-danger">

    <strong>Please correct the following:</strong>

    <ul>

        <?php foreach ((array)$_SESSION['validation_errors'] as $error) : ?>

            <li><?= e((string)$error) ?></li>

        <?php endforeach; ?>

    </ul>

</div>

<?php unset($_SESSION['validation_errors']); ?>

<?php endif; ?>

  <!-- Patient Header -->
    <?php require __DIR__ . '/partials/patient_header.php'; ?>

    <!-- Quick Actions -->
    <?php require __DIR__ . '/partials/quick_actions.php'; ?>

    <?php if ($canViewRegistrationBillingActions): ?>
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>Registration Billing</h2>
                    <p>Initial registration and monthly renewal billing history for this patient.</p>
                </div>
                <div class="form-actions">
                    <?php if (($registrationGate['status'] ?? '') === 'Expired' && $canRequestRegistrationRenewal): ?>
                        <?php if ($pendingRenewalRequest && $permissionService->canViewBillingRequests($currentUser)): ?>
                            <a class="btn-primary" href="../billing/registration_requests.php?<?= e(http_build_query([
                                'status' => 'Pending',
                                'patient_name' => trim((string)($patient['first_name'] ?? '') . ' ' . (string)($patient['last_name'] ?? '')),
                                'hospital_number' => (string)($patient['hospital_number'] ?? ''),
                            ])) ?>">Open Pending Renewal Payment</a>
                        <?php elseif (!$pendingRenewalRequest): ?>
                            <form method="post" action="renew_registration.php" class="inline-form">
                                <?= csrfField() ?>
                                <input type="hidden" name="patient_id" value="<?= (int)$patient['id'] ?>">
                                <button class="btn-primary" type="submit">Create Renewal Payment Request</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($permissionService->canViewBillingRequests($currentUser) || $canViewBilling): ?>
                        <a class="btn-secondary" href="../billing/registration_requests.php?<?= e(http_build_query([
                            'status' => '',
                            'patient_name' => trim((string)($patient['first_name'] ?? '') . ' ' . (string)($patient['last_name'] ?? '')),
                            'hospital_number' => (string)($patient['hospital_number'] ?? ''),
                        ])) ?>">Open Registration Payments</a>
                    <?php endif; ?>
                    <?php if ($canViewBilling): ?>
                        <a class="btn-secondary" href="../billing/index.php?hospital_number=<?= e(urlencode((string)($patient['hospital_number'] ?? ''))) ?>">Billing Home</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($registrationBillingHistory === []): ?>
                <div class="empty-state">No registration billing requests recorded for this patient.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="summary-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Cleared</th>
                                <th>Reference / Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrationBillingHistory as $request): ?>
                                <tr>
                                    <td><?= e((string)$request['billing_type']) ?><?= !empty($request['registration_type']) ? ' / ' . e((string)$request['registration_type']) : '' ?></td>
                                    <td>&#8358;<?= e((string)$request['display_amount']) ?></td>
                                    <td><?= e((string)$request['status']) ?></td>
                                    <td><?= e((string)$request['requested_at']) ?><br><small><?= e((string)($request['requested_by_name'] ?? '-')) ?></small></td>
                                    <td><?= e((string)($request['cleared_at'] ?? '-')) ?><br><small><?= e((string)($request['cleared_by_name'] ?? '')) ?></small></td>
                                    <td><?= e((string)($request['notes'] ?? '-')) ?></td>
                                    <td>
                                        <?php if ((string)$request['status'] === 'Pending' && $canClearRegistrationPayment): ?>
                                            <form method="post" action="../billing/registration_request_pay.php" class="inline-form">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                                                <input type="hidden" name="return_to" value="../patients/view.php?id=<?= (int)$patient['id'] ?>">
                                                <input name="notes" placeholder="Payment reference / notes">
                                                <button class="btn-primary btn-sm" type="submit">Mark Paid</button>
                                            </form>
                                        <?php elseif ((string)$request['status'] === 'Pending' && $permissionService->canViewBillingRequests($currentUser)): ?>
                                            <a class="btn-secondary btn-sm" href="../billing/registration_requests.php?<?= e(http_build_query([
                                                'status' => 'Pending',
                                                'patient_name' => trim((string)($patient['first_name'] ?? '') . ' ' . (string)($patient['last_name'] ?? '')),
                                                'hospital_number' => (string)($patient['hospital_number'] ?? ''),
                                            ])) ?>">Open Payment Request</a>
                                        <?php elseif ((string)$request['status'] === 'Paid'): ?>
                                            <span class="status-badge status-success">Payment Cleared</span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($canDeletePatient && !$isDeletedPatient) : ?>

        <div class="card alert-danger">

            <h2>Delete Patient Registration</h2>

            <p>
                Use this to void a patient registration. Existing encounters, clinical records,
                billing records, documents, and audit history are retained, but the patient is
                hidden from normal search and new encounters are disabled.
            </p>

            <form method="post" action="delete.php" onsubmit="return confirm('Delete this patient registration? This cannot be undone.');">

                <?= csrfField() ?>

                <input type="hidden" name="id" value="<?= (int)$patient['id'] ?>">

                <div class="form-group">

                    <label for="delete_reason">Deletion Reason</label>

                    <textarea id="delete_reason" name="reason" rows="3" required placeholder="Explain why this patient registration should be deleted."></textarea>

                </div>

                <button type="submit" class="btn-danger">Delete Patient</button>

            </form>

        </div>

    <?php endif; ?>

    <!-- Patient Summary -->
    <?php require __DIR__ . '/partials/patient_summary.php'; ?>

<div class="card">

    <h2>Personal Information</h2>

    <div class="review-item">

        <div class="review-label">First Name</div>

        <div class="review-value">

            <?= e($patient['first_name']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Other Names</div>

        <div class="review-value">

            <?= e((string)($patient['middle_name'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Last Name</div>

        <div class="review-value">

            <?= e($patient['last_name']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Gender</div>

        <div class="review-value">

            <?= e($patient['gender']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Date of Birth</div>

        <div class="review-value">

            <?= e($patient['date_of_birth']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Occupation</div>

        <div class="review-value">

            <?= e((string)($patient['occupation'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Place of Work</div>

        <div class="review-value">

            <?= e((string)($patient['place_of_work'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Nationality</div>

        <div class="review-value">

            <?= e((string)($patient['nationality'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Ethnic Group</div>

        <div class="review-value">

            <?= e((string)($patient['ethnic_group'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Religion</div>

        <div class="review-value">

            <?= e((string)($patient['religion'] ?? '')) ?>

        </div>

    </div>

</div>

<div class="card">

    <h2>Contact Information</h2>

    <div class="review-item">

        <div class="review-label">Phone Number</div>

        <div class="review-value">

            <?= e((string)($patient['phone'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">WhatsApp Number</div>

        <div class="review-value">

            <?= e((string)($patient['whatsapp_number'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Email Address</div>

        <div class="review-value">

            <?= e((string)($patient['email'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Address</div>

        <div class="review-value">

            <?= nl2br(e((string)($patient['address'] ?? ''))) ?>

        </div>

    </div>

</div>

<div class="card">

    <h2>Medical Information</h2>

    <div class="review-item">

        <div class="review-label">Blood Group</div>

        <div class="review-value">

            <?= e((string)($patient['blood_group'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Genotype</div>

        <div class="review-value">

            <?= e((string)($patient['genotype'] ?? '')) ?>

        </div>

    </div>

</div>

<div class="card">

    <h2>Next of Kin</h2>

    <div class="review-item">

        <div class="review-label">Full Name</div>

        <div class="review-value">

            <?= e((string)($patient['next_of_kin'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Relationship</div>

        <div class="review-value">

            <?= e((string)($patient['next_of_kin_relationship'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Phone Number</div>

        <div class="review-value">

            <?= e((string)($patient['next_of_kin_phone'] ?? '')) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">Address</div>

        <div class="review-value">

            <?= nl2br(e((string)($patient['next_of_kin_address'] ?? ''))) ?>

        </div>

    </div>

</div>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
