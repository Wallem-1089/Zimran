<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Variables supplied by workspace.php
|--------------------------------------------------------------------------
*/

if (!isset($visit, $patient)) {
    return;
}

?>

<section
    id="tab-consultation"
    class="workspace-tab">

    <div class="card">

        <div class="card-header">

            <div>

                <h2>

                    Consultation

                </h2>

                <p>

                    Doctor consultation and clinical assessment.

                </p>

            </div>

            <div>

                <a
                    href="../../doctors/consultation/start.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-primary">

                    Start Consultation

                </a>

            </div>

        </div>

        <div class="summary-grid">

            <div class="summary-item">

                <span class="summary-label">

                    Encounter

                </span>

                <span class="summary-value">

                    #<?= (int)$visit['id'] ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Hospital Number

                </span>

                <span class="summary-value">

                    <?= e($patient['hospital_number']) ?>

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Consultation Status

                </span>

                <span class="summary-value status-pending">

                    Pending

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Assigned Doctor

                </span>

                <span class="summary-value">

                    Not Assigned

                </span>

            </div>

        </div>

    </div>

    <div class="card">

        <h3>

            Chief Complaint

        </h3>

        <div class="empty-state">

            No chief complaint has been recorded.

        </div>

    </div>

    <div class="card">

        <h3>

            History of Present Illness

        </h3>

        <div class="empty-state">

            No clinical history available.

        </div>

    </div>

    <div class="card">

        <h3>

            Physical Examination

        </h3>

        <div class="empty-state">

            No examination findings recorded.

        </div>

    </div>

    <div class="card">

        <h3>

            Diagnosis

        </h3>

        <div class="empty-state">

            No diagnosis has been entered.

        </div>

    </div>

    <div class="card">

        <h3>

            Treatment Plan

        </h3>

        <div class="empty-state">

            No treatment plan available.

        </div>

    </div>

    <div class="card">

        <h3>

            Medications

        </h3>

        <div class="empty-state">

            No medications prescribed.

        </div>

    </div>

    <div class="card">

        <h3>

            Investigation Requests

        </h3>

        <div class="empty-state">

            No laboratory or radiology requests.

        </div>

    </div>

    <div class="card">

        <h3>

            Follow-up

        </h3>

        <div class="empty-state">

            No follow-up appointment scheduled.

        </div>

    </div>

</section>