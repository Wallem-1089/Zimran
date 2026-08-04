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
    id="tab-radiology"
    class="workspace-tab">

    <div class="card">

        <div class="card-header">

            <div>

                <h2>

                    Radiology

                </h2>

                <p>

                    Imaging requests, scheduled examinations and radiology
                    reports.

                </p>

            </div>

            <div>

                <a
                    href="../../radiology/request.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-primary">

                    Request Imaging

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

                    Latest Report

                </span>

                <span class="summary-value">

                    None

                </span>

            </div>

        </div>

    </div>

    <div class="card">

        <h3>

            Imaging Requests

        </h3>

        <div class="empty-state">

            No radiology investigations have been requested for this
            encounter.

        </div>

    </div>

    <div class="card">

        <h3>

            Examination Schedule

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Status

                    </th>

                    <td>

                        Not Scheduled

                    </td>

                </tr>

                <tr>

                    <th>

                        Appointment Date

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Radiographer

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Reporting Doctor

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

            Imaging Studies

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Chest X-Ray

                    </th>

                    <td>

                        Not Requested

                    </td>

                </tr>

                <tr>

                    <th>

                        Abdominal Ultrasound

                    </th>

                    <td>

                        Not Requested

                    </td>

                </tr>

                <tr>

                    <th>

                        Pelvic Ultrasound

                    </th>

                    <td>

                        Not Requested

                    </td>

                </tr>

                <tr>

                    <th>

                        CT Scan

                    </th>

                    <td>

                        Not Requested

                    </td>

                </tr>

                <tr>

                    <th>

                        MRI

                    </th>

                    <td>

                        Not Requested

                    </td>

                </tr>

                <tr>

                    <th>

                        Mammography

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

            Radiologist Report

        </h3>

        <div class="empty-state">

            No radiology report has been uploaded.

        </div>

    </div>

    <div class="card">

        <h3>

            Image Archive

        </h3>

        <div class="empty-state">

            No diagnostic images are available.

        </div>

    </div>

    <div class="card">

        <h3>

            Radiology Notes

        </h3>

        <div class="empty-state">

            No radiology notes available.

        </div>

    </div>

    <div class="card">

        <h3>

            Previous Imaging History

        </h3>

        <div class="empty-state">

            No previous radiology examinations found.

        </div>

    </div>

</section>