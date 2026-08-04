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
    id="tab-nursing"
    class="workspace-tab">

    <div class="card">

        <div class="card-header">

            <div>

                <h2>

                    Nursing Assessment

                </h2>

                <p>

                    Vital signs, triage assessment and nursing observations.

                </p>

            </div>

            <div>

                <a
                    href="../../nursing/assessment.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-primary">

                    Start Assessment

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

                    Assessment Status

                </span>

                <span class="summary-value status-pending">

                    Pending

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Assigned Nurse

                </span>

                <span class="summary-value">

                    Not Assigned

                </span>

            </div>

        </div>

    </div>

    <div class="card">

        <h3>

            Vital Signs

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Temperature

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Blood Pressure

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Pulse Rate

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Respiratory Rate

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Oxygen Saturation

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Weight

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Height

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        BMI

                    </th>

                    <td>

                        —

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Triage Assessment

        </h3>

        <div class="empty-state">

            No triage assessment has been recorded.

        </div>

    </div>

    <div class="card">

        <h3>

            Allergies

        </h3>

        <div class="empty-state">

            No allergy information available.

        </div>

    </div>

    <div class="card">

        <h3>

            Nursing Notes

        </h3>

        <div class="empty-state">

            No nursing notes have been recorded.

        </div>

    </div>

    <div class="card">

        <h3>

            Patient Observations

        </h3>

        <div class="empty-state">

            No patient observations recorded.

        </div>

    </div>

    <div class="card">

        <h3>

            Nursing Procedures

        </h3>

        <div class="empty-state">

            No nursing procedures have been performed.

        </div>

    </div>

    <div class="card">

        <h3>

            Medication Administration

        </h3>

        <div class="empty-state">

            No medications have been administered.

        </div>

    </div>

</section>