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
    id="tab-pharmacy"
    class="workspace-tab">

    <div class="card">

        <div class="card-header">

            <div>

                <h2>

                    Pharmacy

                </h2>

                <p>

                    Prescriptions, medication dispensing and pharmacy records.

                </p>

            </div>

            <div>

                <a
                    href="../../pharmacy/prescribe.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-primary">

                    New Prescription

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

                    Active Prescriptions

                </span>

                <span class="summary-value">

                    0

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Dispensed

                </span>

                <span class="summary-value">

                    None

                </span>

            </div>

        </div>

    </div>

    <div class="card">

        <h3>

            Current Prescriptions

        </h3>

        <div class="empty-state">

            No medications have been prescribed for this encounter.

        </div>

    </div>

    <div class="card">

        <h3>

            Dispensing Status

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Pharmacy Status

                    </th>

                    <td>

                        Awaiting Prescription

                    </td>

                </tr>

                <tr>

                    <th>

                        Dispensed By

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Dispensed Date

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Outstanding Items

                    </th>

                    <td>

                        None

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Medication List

        </h3>

        <table class="summary-table">

            <thead>

                <tr>

                    <th>

                        Medication

                    </th>

                    <th>

                        Dose

                    </th>

                    <th>

                        Frequency

                    </th>

                    <th>

                        Duration

                    </th>

                    <th>

                        Status

                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td
                        colspan="5"
                        class="text-center">

                        No prescribed medications.

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Allergy Alert

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Known Allergies

                    </th>

                    <td>

                        <?= e($patient['allergies'] ?? 'None Recorded') ?>

                    </td>

                </tr>

                <tr>

                    <th>

                        Drug Interaction Warning

                    </th>

                    <td>

                        None

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Dispensing Notes

        </h3>

        <div class="empty-state">

            No pharmacy notes available.

        </div>

    </div>

    <div class="card">

        <h3>

            Medication History

        </h3>

        <div class="empty-state">

            No previous prescriptions available.

        </div>

    </div>

</section>