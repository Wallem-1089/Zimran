<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';

require_once __DIR__ . '/../../services/VisitService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$pageTitle = 'Receive Patient';

$moduleStylesheet =
    '/modules/visits/assets/visits.css';

$moduleScript =
    '/modules/visits/assets/visits.js';

/*
|--------------------------------------------------------------------------
| Visit
|--------------------------------------------------------------------------
*/

$visitId = filter_input(

    INPUT_GET,

    'visit',

    FILTER_VALIDATE_INT

);

if (!$visitId) {

    header('Location: ../patients/search.php');

    exit;

}

$visitService = new VisitService($pdo);

$visit = $visitService->getVisitById($visitId);

if (!$visit) {

    http_response_code(404);

    exit('Encounter not found.');

}

$permissionService = new PermissionService($pdo);

if (!$permissionService->canViewEncounter($visit, $currentUser)) {
    securityFailure(
        'You do not have permission to access this encounter.',
        $visitId,
        'RECEIVE_ACCESS_DENIED'
    );
}

$errorMessage = $_SESSION['error_message'] ?? null;

unset($_SESSION['error_message']);

$hasPendingTransfer = $visitService->hasPendingTransfer($visitId);

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

?>

<div class="main-container">

<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">

<?php if ($errorMessage !== null): ?>

<div class="alert-danger">

    <?= nl2br(e((string)$errorMessage)) ?>

</div>

<?php endif; ?>

<div class="page-header">

    <div>

        <h1>

            Receive Patient

        </h1>

        <p>

            Confirm that this department has received the patient.

        </p>

    </div>

</div>

<div class="card">

    <h2>

        Encounter Details

    </h2>

    <div class="review-item">

        <div class="review-label">

            Visit Number

        </div>

        <div class="review-value">

            <?= e($visit['visit_number']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">

            Patient

        </div>

        <div class="review-value">

            <?= e($visit['first_name']) ?>

            <?= e($visit['last_name']) ?>

        </div>

    </div>

    <div class="review-item">

        <div class="review-label">

            Current Department

        </div>

        <div class="review-value">

            <?= e($visit['department_name']) ?>

        </div>

    </div>

</div>

<?php if ($hasPendingTransfer): ?>

<form
    method="POST"
    action="receive_save.php"
    class="card">

<?= csrfField() ?>

<input
    type="hidden"
    name="visit_id"
    value="<?= (int)$visit['id'] ?>">

<div class="alert-info">

    By clicking <strong>Receive Patient</strong>,
    you confirm that your department has accepted
    responsibility for this encounter.

</div>

<div class="form-actions">

<a
    href="workspace.php?id=<?= (int)$visit['id'] ?>"
    class="btn-secondary">

    Cancel

</a>

<button
    type="submit"
    class="btn-primary">

    Receive Patient

</button>

</div>

</form>

<?php else: ?>

<div class="alert-info">

    This encounter has no pending department transfer awaiting receipt.

    The receive action is not available until a transfer is recorded.

</div>

<?php endif; ?>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

</div>
