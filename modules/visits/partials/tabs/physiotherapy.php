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
    id="tab-physiotherapy"
    class="workspace-tab">

    <div class="card">

        <div class="card-header">

            <div>

                <h2>

                    Physiotherapy

                </h2>

                <p>

                    Physiotherapy referrals, treatment sessions, rehabilitation
                    plans and progress monitoring.

                </p>

            </div>

            <div>

                <a
                    href="../../physiotherapy/refer.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-primary">

                    Refer to Physiotherapy

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

                    Referral Status

                </span>

                <span class="summary-value">

                    Not Referred

                </span>

            </div>

            <div class="summary-item">

                <span class="summary-label">

                    Treatment Sessions

                </span>

                <span class="summary-value">

                    0

                </span>

            </div>

        </div>

    </div>

    <div class="card">

        <h3>

            Physiotherapy Referral

        </h3>

        <div class="empty-state">

            No physiotherapy referral has been made for this encounter.

        </div>

    </div>

    <div class="card">

        <h3>

            Rehabilitation Plan

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Diagnosis

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Rehabilitation Goal

                    </th>

                    <td>

                        —

                    </td>

                </tr>

                <tr>

                    <th>

                        Treatment Plan

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

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Therapy Sessions

        </h3>

        <table class="summary-table">

            <thead>

                <tr>

                    <th>

                        Date

                    </th>

                    <th>

                        Therapist

                    </th>

                    <th>

                        Procedure

                    </th>

                    <th>

                        Outcome

                    </th>

                </tr>

            </thead>

            <tbody>

                <tr>

                    <td
                        colspan="4"
                        class="text-center">

                        No physiotherapy sessions recorded.

                    </td>

                </tr>

            </tbody>

        </table>

    </div>

    <div class="card">

        <h3>

            Functional Assessment

        </h3>

        <table class="summary-table">

            <tbody>

                <tr>

                    <th>

                        Mobility

                    </th>

                    <td>

                        Not Assessed

                    </td>

                </tr>

                <tr>

                    <th>

                        Muscle Strength

                    </th>

                    <td>

                        Not Assessed

                    </td>

                </tr>

                <tr>

                    <th>

                        Range of Motion

                    </th>

                    <td>

                        Not Assessed

                    </td>

                </tr>

                <tr>

                    <th>

                        Pain Score

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

            Progress Notes

        </h3>

        <div class="empty-state">

            No physiotherapy progress notes available.

        </div>

    </div>

    <div class="card">

        <h3>

            Discharge Summary

        </h3>

        <div class="empty-state">

            No physiotherapy discharge summary available.

        </div>

    </div>

</section>