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
    id="tab-notes"
    class="workspace-tab">

    <div class="card">

        <div class="card-header">

            <div>

                <h2>

                    Clinical Notes

                </h2>

                <p>

                    View and manage encounter notes, observations, handover notes
                    and multidisciplinary comments.

                </p>

            </div>

            <div>

                <a
                    href="../../notes/create.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-primary">

                    Add Note

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

                    Total Notes

                </span>

                <span class="summary-value">

                    0

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Last Updated

                </span>

                <span class="summary-value">

                    Never

                </span>

            </div>

        </div>

    </div>

    <div class="card">

        <h3>

            Encounter Notes

        </h3>

        <div class="empty-state">

            No clinical notes have been recorded for this encounter.

        </div>

    </div>

    <div class="card">

        <h3>

            Doctor's Notes

        </h3>

        <div class="empty-state">

            No doctor's notes available.

        </div>

    </div>

    <div class="card">

        <h3>

            Nursing Notes

        </h3>

        <div class="empty-state">

            No nursing notes available.

        </div>

    </div>

    <div class="card">

        <h3>

            Progress Notes

        </h3>

        <table class="summary-table">

            <thead>

                <tr>

                    <th>

                        Date

                    </th>

                    <th>

                        Department

                    </th>

                    <th>

                        Staff

                    </th>

                    <th>

                        Summary

                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td
                        colspan="4"
                        class="text-center">

                        No progress notes recorded.

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Handover Notes

        </h3>

        <div class="empty-state">

            No handover notes available.

        </div>

    </div>

    <div class="card">

        <h3>

            Multidisciplinary Team Notes

        </h3>

        <div class="empty-state">

            No multidisciplinary comments have been added.

        </div>

    </div>

    <div class="card">

        <h3>

            Private Notes

        </h3>

        <div class="empty-state">

            No private notes recorded.

        </div>

    </div>

    <div class="card">

        <h3>

            Audit Trail

        </h3>

        <table class="summary-table">

            <thead>

                <tr>

                    <th>

                        Date

                    </th>

                    <th>

                        User

                    </th>

                    <th>

                        Action

                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td
                        colspan="3"
                        class="text-center">

                        No audit records available.

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</section>