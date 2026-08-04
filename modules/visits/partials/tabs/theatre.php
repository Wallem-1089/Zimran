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
    id="tab-theatre"
    class="workspace-tab">

    <div class="card">

        <div class="card-header">

            <div>

                <h2>

                    Theatre

                </h2>

                <p>

                    Surgical requests, theatre scheduling, operative procedures
                    and post-operative management.

                </p>

            </div>

            <div>

                <a
                    href="../../theatre/request.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-primary">

                    Request Surgery

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

                    Theatre Status

                </span>

                <span class="summary-value">

                    No Surgery Requested

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Operations

                </span>

                <span class="summary-value">

                    0

                </span>

            </div>

        </div>

    </div>

    <div class="card">

        <h3>

            Surgical Request

        </h3>

        <div class="empty-state">

            No surgical request has been submitted for this encounter.

        </div>

    </div>

    <div class="card">

        <h3>

            Theatre Schedule

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Surgery Date

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Theatre Room

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Lead Surgeon

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Anaesthetist

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

            Planned Procedure

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Procedure

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Priority

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Estimated Duration

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Current Status

                    </th>

                    <td>

                        Awaiting Scheduling

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Operative Report

        </h3>

        <div class="empty-state">

            No operative report available.

        </div>

    </div>

    <div class="card">

        <h3>

            Post-Operative Care

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Recovery Status

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Ward Transfer

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Follow-up Required

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Complications

                    </th>

                    <td>

                        None Recorded

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Surgical Team

        </h3>

        <table class="summary-table">

            <thead>

                <tr>

                    <th>

                        Role

                    </th>

                    <th>

                        Staff Member

                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td>

                        Surgeon

                    </td>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <td>

                        Assistant Surgeon

                    </td>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <td>

                        Anaesthetist

                    </td>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <td>

                        Theatre Nurse

                    </td>

                    <td>

                        —

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Theatre Notes

        </h3>

        <div class="empty-state">

            No theatre notes available for this encounter.

        </div>

    </div>

</section>