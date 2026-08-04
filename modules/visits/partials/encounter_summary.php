<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Required Variables
|--------------------------------------------------------------------------
|
| $visit
|
*/

if (!isset($visit)) {

    return;

}

?>

<div class="card">

    <h2>

        Encounter Summary

    </h2>

    <div class="patient-summary">

        <div class="summary-card">

            <h3>

                Visit Information

            </h3>

            <div class="summary-item">

                <strong>

                    Visit Number

                </strong>

                <span>

                    <?= e($visit['visit_number']) ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>

                    Visit Type

                </strong>

                <span>

                    <?= e($visit['visit_type']) ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>

                    Current Status

                </strong>

                <span>

                    <?= e($visit['visit_status']) ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>

                    Current Department

                </strong>

                <span>

                    <?= e(

                        $visit['department_name']

                        ?? 'Not Assigned'

                    ) ?>

                </span>

            </div>

        </div>

        <div class="summary-card">

            <h3>

                Clinical Assignment

            </h3>

            <div class="summary-item">

                <strong>

                    Department

                </strong>

                <span>

                    <?= e(

                        $visit['department_name']

                        ?? 'Not Assigned'

                    ) ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>

                    Attending Doctor

                </strong>

                <span>

                    <?= e(

                        $visit['doctor_name']

                        ?? 'Not Assigned'

                    ) ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>

                    Department ID

                </strong>

                <span>

                    <?= e(

                        $visit['current_department_id']

                        ?? '-'

                    ) ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>

                    Doctor ID

                </strong>

                <span>

                    <?= e(

                        $visit['attending_doctor_id']

                        ?? '-'

                    ) ?>

                </span>

            </div>

        </div>

        <div class="summary-card">

            <h3>

                Registration

            </h3>

            <div class="summary-item">

                <strong>

                    Visit Date

                </strong>

                <span>

                    <?= !empty($visit['visit_date'])

                        ? e(

                            date(

                                'd M Y h:i A',

                                strtotime(

                                    $visit['visit_date']

                                )

                            )

                        )

                        : '-' ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>

                    Registered By

                </strong>

                <span>

                    <?= e(

                        $visit['registered_by_name']

                        ?? '-'

                    ) ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>

                    Last Updated

                </strong>

                <span>

                    <?= !empty($visit['updated_at'])

                        ? e(

                            date(

                                'd M Y h:i A',

                                strtotime(

                                    $visit['updated_at']

                                )

                            )

                        )

                        : '-' ?>

                </span>

            </div>

            <div class="summary-item">

                <strong>

                    Encounter ID

                </strong>

                <span>

                    #<?= e($visit['id']) ?>

                </span>

            </div>

        </div>

    </div>

</div>