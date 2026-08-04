<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Required Variables
|--------------------------------------------------------------------------
|
| Expected:
| $visit
|
*/

if (!isset($visit)) {

    return;

}

?>

<div class="card">

    <h2>

        Quick Actions

    </h2>

    <div class="patient-actions-grid">

        <!-- Patient Profile -->

        <a
            href="../patients/view.php?id=<?= (int)$visit['patient_id'] ?>"
            class="action-card action-primary">

            <div class="action-icon">

                👤

            </div>

            <div class="action-content">

                <strong>

                    Patient Profile

                </strong>

                <span>

                    View complete patient information

                </span>

            </div>

        </a>

        <!-- Edit Patient -->

        <a
            href="../patients/edit.php?id=<?= (int)$visit['patient_id'] ?>"
            class="action-card">

            <div class="action-icon">

                ✏️

            </div>

            <div class="action-content">

                <strong>

                    Edit Patient

                </strong>

                <span>

                    Update patient information

                </span>

            </div>

        </a>

        <!-- Encounter History -->

        <a
            href="../patients/history.php?id=<?= (int)$visit['patient_id'] ?>"
            class="action-card">

            <div class="action-icon">

                📜

            </div>

            <div class="action-content">

                <strong>

                    Encounter History

                </strong>

                <span>

                    View all patient encounters

                </span>

            </div>

        </a>

        <!-- Search Patients -->

        <a
            href="../patients/search.php"
            class="action-card">

            <div class="action-icon">

                🔍

            </div>

            <div class="action-content">

                <strong>

                    Search Patients

                </strong>

                <span>

                    Find another patient

                </span>

            </div>

        </a>

    </div>

</div>