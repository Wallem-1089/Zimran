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
    id="tab-documents"
    class="workspace-tab">

    <div class="card">

        <div class="card-header">

            <div>

                <h2>

                    Documents

                </h2>

                <p>

                    Manage all encounter-related documents, reports and attachments.

                </p>

            </div>

            <div>

                <a
                    href="../../documents/upload.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-primary">

                    Upload Document

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

                    Total Documents

                </span>

                <span class="summary-value">

                    0

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Last Upload

                </span>

                <span class="summary-value">

                    None

                </span>

            </div>

        </div>

    </div>

    <div class="card">

        <h3>

            Uploaded Documents

        </h3>

        <table class="summary-table">

            <thead>

                <tr>

                    <th>

                        Document

                    </th>

                    <th>

                        Category

                    </th>

                    <th>

                        Uploaded By

                    </th>

                    <th>

                        Date

                    </th>

                    <th>

                        Actions

                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td
                        colspan="5"
                        class="text-center">

                        No documents have been uploaded.

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Document Categories

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Consultation Notes

                    </th>

                    <td>

                        0

                    </td>

                </tr>

                <tr>

                    <th>

                        Laboratory Reports

                    </th>

                    <td>

                        0

                    </td>

                </tr>

                <tr>

                    <th>

                        Radiology Images

                    </th>

                    <td>

                        0

                    </td>

                </tr>

                <tr>

                    <th>

                        Prescriptions

                    </th>

                    <td>

                        0

                    </td>

                </tr>

                <tr>

                    <th>

                        Theatre Reports

                    </th>

                    <td>

                        0

                    </td>

                </tr>

                <tr>

                    <th>

                        Consent Forms

                    </th>

                    <td>

                        0

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Recent Upload Activity

        </h3>

        <div class="empty-state">

            No document activity available for this encounter.

        </div>

    </div>

    <div class="card">

        <h3>

            Required Documents

        </h3>

        <table class="summary-table">

            <thead>

                <tr>

                    <th>

                        Document

                    </th>

                    <th>

                        Status

                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td>

                        Patient Consent Form

                    </td>

                    <td>

                        Pending

                    </td>

                </tr>

                <tr>

                    <td>

                        Clinical Notes

                    </td>

                    <td>

                        Pending

                    </td>

                </tr>

                <tr>

                    <td>

                        Investigation Results

                    </td>

                    <td>

                        Pending

                    </td>

                </tr>

                <tr>

                    <td>

                        Discharge Summary

                    </td>

                    <td>

                        Pending

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Archive

        </h3>

        <div class="empty-state">

            No archived documents available.

        </div>

    </div>

</section>