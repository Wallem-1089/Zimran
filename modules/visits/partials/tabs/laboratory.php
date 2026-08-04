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
    id="tab-laboratory"
    class="workspace-tab">

    <div class="card">

        <div class="card-header">

            <div>

                <h2>

                    Laboratory

                </h2>

                <p>

                    Laboratory requests, specimen collection and investigation
                    results.

                </p>

            </div>

            <div>

                <a
                    href="../../laboratory/request.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-primary">

                    Request Investigation

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

                    Pending Requests

                </span>

                <span class="summary-value">

                    0

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Latest Result

                </span>

                <span class="summary-value">

                    None

                </span>

            </div>

        </div>

    </div>

    <div class="card">

        <h3>

            Investigation Requests

        </h3>

        <div class="empty-state">

            No laboratory investigations have been requested for this
            encounter.

        </div>

    </div>

    <div class="card">

        <h3>

            Specimen Collection

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Collection Status

                    </th>

                    <td>

                        Not Collected

                    </td>

                </tr>

                <tr>

                    <th>

                        Collection Date

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Collected By

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

            Test Results

        </h3>

        <div class="empty-state">

            No laboratory results available.

        </div>

    </div>

    <div class="card">

        <h3>

            Common Investigations

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Full Blood Count (FBC)

                    </th>

                    <td>

                        Not Requested

                    </td>

                </tr>

                <tr>

                    <th>

                        Malaria Parasite (MP)

                    </th>

                    <td>

                        Not Requested

                    </td>

                </tr>

                <tr>

                    <th>

                        Urinalysis

                    </th>

                    <td>

                        Not Requested

                    </td>

                </tr>

                <tr>

                    <th>

                        Blood Sugar

                    </th>

                    <td>

                        Not Requested

                    </td>

                </tr>

                <tr>

                    <th>

                        Liver Function Test

                    </th>

                    <td>

                        Not Requested

                    </td>

                </tr>

                <tr>

                    <th>

                        Kidney Function Test

                    </th>

                    <td>

                        Not Requested

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Laboratory Notes

        </h3>

        <div class="empty-state">

            No laboratory notes available.

        </div>

    </div>

    <div class="card">

        <h3>

            Result History

        </h3>

        <div class="empty-state">

            No previous laboratory investigations found.

        </div>

    </div>

</section>