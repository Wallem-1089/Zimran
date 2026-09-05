<?php

declare(strict_types=1);

$pageTitle = 'Patient Search';

/*
|--------------------------------------------------------------------------
| Module Assets
|--------------------------------------------------------------------------
|
| Loaded automatically by header.php and footer.php.
|
*/

$moduleStylesheet = '/modules/patients/assets/patients.css';
$moduleScript = '/modules/patients/assets/patients.js';

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/VisitService.php';

$currentUser = $currentUser ?? ($_SESSION['user'] ?? null);
$permissionService = new PermissionService($pdo);
$canRegisterPatient = $permissionService->canRegisterPatient($currentUser);

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

?>

<div class="main-container">

<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>

<main class="content">

<div class="page-header">

    <div>

        <h1>Patient Search</h1>

        <p>

            Search for an existing patient or register a new patient.

        </p>

    </div>

    <?php if ($canRegisterPatient): ?>
    <div>

        <a
            href="register.php"
            class="btn-primary">

            + Register Patient

        </a>

    </div>
    <?php endif; ?>

</div>

<div class="card">

<form
    method="GET"
    action="search.php">

    <div class="form-grid">

        <div class="form-group">

            <label>Hospital Number</label>

            <input
                type="text"
                name="hospital_number"
                value="<?= e($_GET['hospital_number'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label>First Name</label>

            <input
                type="text"
                name="first_name"
                value="<?= e($_GET['first_name'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label>Last Name</label>

            <input
                type="text"
                name="last_name"
                value="<?= e($_GET['last_name'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label>Phone Number</label>

            <input
                type="text"
                name="phone"
                value="<?= e($_GET['phone'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label>Date of Birth</label>

            <input
                type="date"
                name="date_of_birth"
                value="<?= e($_GET['date_of_birth'] ?? '') ?>">

        </div>

        <div class="form-group">

            <label>Gender</label>

            <select name="gender">

                <option value="">Any</option>

                <?php foreach (PatientService::supportedGenders() as $genderOption): ?>

                    <option
                        value="<?= e($genderOption) ?>"
                        <?= ($_GET['gender'] ?? '') === $genderOption ? 'selected' : '' ?>>

                        <?= e($genderOption) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

    </div>

    <div class="form-actions">

        <button
            class="btn-primary"
            type="submit">

            Search

        </button>

    </div>

</form>

</div>

<?php

$patients = [];

if (!empty($_GET)) {

    require_once __DIR__ . '/../../services/PatientService.php';

    /*
    |--------------------------------------------------------------------------
    | Create Patient Service
    |--------------------------------------------------------------------------
    */

    $patientService = new PatientService($pdo);
    $visitService = new VisitService($pdo);

    /*
    |--------------------------------------------------------------------------
    | Search Patients
    |--------------------------------------------------------------------------
    */

    $patients = $patientService->searchPatients($_GET);

}

?>

<div class="card">

<h2>Search Results</h2>

<?php if (empty($_GET)): ?>

<p>

Enter one or more search criteria.

</p>

<?php elseif (empty($patients)): ?>

<p>

No patients found.

</p>

<?php else: ?>

<div class="table-responsive">

<table>

<thead>

<tr>

<th>Hospital No.</th>

<th>Name</th>

<th>Gender</th>

<th>Phone</th>

<th>Active Encounter</th>

<th>Action</th>

</tr>

</thead>

<tbody>

<?php foreach ($patients as $patient): ?>
<?php $activeVisit = $visitService->getActiveVisit((int)$patient['id']); ?>

<tr>

<td>

<?= e($patient['hospital_number']) ?>

</td>

<td>

<?= e($patient['first_name']) ?>

<?= e($patient['last_name']) ?>

</td>

<td>

<?= e($patient['gender']) ?>

</td>

<td>

<?= e($patient['phone']) ?>

</td>

<td>

<?php if ($activeVisit): ?>

    <strong><?= e((string)$activeVisit['visit_number']) ?></strong><br>
    <small>
        <?= e((string)($activeVisit['department_name'] ?? '')) ?>
        <?= !empty($activeVisit['visit_status']) ? ' - ' . e((string)$activeVisit['visit_status']) : '' ?>
    </small>

<?php else: ?>

    <span class="text-muted">No active encounter</span>

<?php endif; ?>

</td>

<td>

<a
    href="view.php?id=<?= (int)$patient['id'] ?>"
    class="btn-secondary">

    View

</a>

<?php if ($permissionService->canViewMedicalRecord((int)$patient['id'], $currentUser)): ?>

<a
    href="../medical_records/chart.php?patient=<?= (int)$patient['id'] ?>"
    class="btn-secondary">

    View Patient Chart

</a>

<?php endif; ?>

<?php if ($activeVisit): ?>

<a
    href="../visits/workspace.php?id=<?= (int)$activeVisit['id'] ?>"
    class="btn-primary">

    Open Encounter

</a>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php endif; ?>

</div>

</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
