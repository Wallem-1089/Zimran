<?php

declare(strict_types=1);

$pageTitle = 'Medical Records';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PermissionService.php';

$permissionService = new PermissionService($pdo);

if (!$permissionService->hasPermission('view_medical_record', $currentUser)) {
    http_response_code(403);
    exit('You do not have permission to access Medical Records.');
}

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

?>

<div class="main-container">

<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">

    <div class="page-header">

        <div>

            <h1>Medical Records</h1>

            <p>Open a longitudinal Patient Chart through an identified patient.</p>

        </div>

    </div>

    <div class="card medical-records-entry">

        <h2>Find a Patient Chart</h2>

        <p>
            Patient Charts are never opened without a patient context. Search for
            the patient first, then select <strong>View Patient Chart</strong>.
        </p>

        <a href="../patients/search.php" class="btn-primary">Search Patients</a>

        <a href="mpi/index.php" class="btn-secondary">Master Patient Index</a>

        <?php if ($permissionService->canViewDuplicateCandidates($currentUser)): ?>
            <a href="mpi/candidates.php" class="btn-secondary">Duplicate Cases</a>
        <?php endif; ?>

    </div>

    <div class="card">

        <h2>Foundation Status</h2>

        <p>
            Demographics, encounters, demographic amendments, and authorized audit
            history are available in this milestone.
        </p>

        <p class="text-muted">
            Alternate identifiers, duplicate review, and Clinical Safety are available.
            Patient identifiers, Clinical Safety, Problem List, structured history,
            Medical Documents, and Clinical Notes are available through an
            authorized patient chart. Consultation documentation remains planned.
        </p>

    </div>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

</div>
