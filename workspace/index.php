<?php

declare(strict_types=1);

$pageTitle = 'Encounter Workspace';

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="main-container">

<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<main class="content">

<?php

$visitId = isset($_GET['visit']) ? (int) $_GET['visit'] : 0;

if ($visitId <= 0) {
    ?>

    <div class="card">

        <h2>No Encounter Selected</h2>

        <p>

            No encounter was supplied.

            Please open an encounter from Reception,
            Patients or the Dashboard.

        </p>

    </div>

    </main>

    <?php require_once __DIR__ . '/../layouts/footer.php'; ?>

    <?php

    exit;
}

/*
|--------------------------------------------------------------------------
| Temporary Encounter Data
|--------------------------------------------------------------------------
|
| This will later come from VisitService.
|
*/

$encounter = [

    'visit_id' => $visitId,

    'hospital_number' => 'HMS-000001',

    'patient_name' => 'John Doe',

    'gender' => 'Male',

    'age' => 34,

    'status' => 'Consultation',

    'doctor' => 'Dr. Smith',

    'visit_date' => date('d M Y H:i')

];

$activeTab = $_GET['tab'] ?? 'overview';

?>

<section class="workspace-header">

    <div>

        <h2>

            Encounter #

            <?= e((string)$encounter['visit_id']) ?>

        </h2>

        <p>

            <?= e($encounter['hospital_number']) ?>

            •

            <?= e($encounter['patient_name']) ?>

            •

            <?= e((string)$encounter['age']) ?> Years

            •

            <?= e($encounter['gender']) ?>

        </p>

    </div>

    <div>

        <strong>

            Status:

            <?= e($encounter['status']) ?>

        </strong>

        <br>

        <?= e($encounter['visit_date']) ?>

    </div>

</section>

<nav class="workspace-tabs">

    <a
        href="?visit=<?= $visitId ?>&tab=overview"
        class="<?= $activeTab === 'overview' ? 'active' : '' ?>">

        Overview

    </a>

    <a
        href="?visit=<?= $visitId ?>&tab=consultation"
        class="<?= $activeTab === 'consultation' ? 'active' : '' ?>">

        Consultation

    </a>

    <a
        href="?visit=<?= $visitId ?>&tab=nursing"
        class="<?= $activeTab === 'nursing' ? 'active' : '' ?>">

        Nursing

    </a>

    <a
        href="?visit=<?= $visitId ?>&tab=laboratory"
        class="<?= $activeTab === 'laboratory' ? 'active' : '' ?>">

        Laboratory

    </a>

    <a
        href="?visit=<?= $visitId ?>&tab=xray"
        class="<?= $activeTab === 'xray' ? 'active' : '' ?>">

        X-Ray

    </a>

    <a
        href="?visit=<?= $visitId ?>&tab=pharmacy"
        class="<?= $activeTab === 'pharmacy' ? 'active' : '' ?>">

        Pharmacy

    </a>

    <a
        href="?visit=<?= $visitId ?>&tab=billing"
        class="<?= $activeTab === 'billing' ? 'active' : '' ?>">

        Billing

    </a>

    <a
        href="?visit=<?= $visitId ?>&tab=timeline"
        class="<?= $activeTab === 'timeline' ? 'active' : '' ?>">

        Timeline

    </a>

</nav>

<section class="workspace-body">

<?php

switch ($activeTab) {

    case 'consultation':

        echo '<h3>Consultation Module</h3>';
        echo '<p>This module will contain consultation notes.</p>';
        break;

    case 'nursing':

        echo '<h3>Nursing Module</h3>';
        echo '<p>Nursing observations will appear here.</p>';
        break;

    case 'laboratory':

        echo '<h3>Laboratory Module</h3>';
        echo '<p>Laboratory requests and results.</p>';
        break;

    case 'xray':

        echo '<h3>X-Ray Module</h3>';
        echo '<p>Radiology requests and reports.</p>';
        break;

    case 'pharmacy':

        echo '<h3>Pharmacy Module</h3>';
        echo '<p>Prescriptions and dispensing.</p>';
        break;

    case 'billing':

        echo '<h3>Billing Module</h3>';
        echo '<p>Accounts and payment records.</p>';
        break;

    case 'timeline':

        echo '<h3>Timeline</h3>';
        echo '<p>Complete encounter history.</p>';
        break;

    default:

        echo '<h3>Encounter Overview</h3>';
        echo '<p>Patient summary and encounter information.</p>';
        break;

}

?>

</section>

</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
