<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Required Variables
|--------------------------------------------------------------------------
|
| Expected:
| $patient
| $visit
|
*/

if (

    !isset($patient)

    ||

    !isset($visit)

) {

    return;

}

/*
|--------------------------------------------------------------------------
| Calculate Age
|--------------------------------------------------------------------------
*/

$age = '-';

if (!empty($patient['date_of_birth'])) {

    try {

        $dob = new DateTime(

            $patient['date_of_birth']

        );

        $age =

            $dob->diff(

                new DateTime()

            )->y . ' years';

    } catch (Throwable $e) {

        $age = '-';

    }

}

/*
|--------------------------------------------------------------------------
| Visit Status Badge
|--------------------------------------------------------------------------
*/

$statusClass = strtolower(

    str_replace(

        ' ',

        '-',

        $visit['visit_status']

    )

);

/*
|--------------------------------------------------------------------------
| Department Receive Status
|--------------------------------------------------------------------------
*/

$received = (

    $visit['current_department_received_status']

    ?? 'Pending'

) === 'Received';

/*
|--------------------------------------------------------------------------
| Transfer-backed Reception State
|--------------------------------------------------------------------------
|
| A department reception is actionable only when a pending transfer exists.
| Older encounters may contain a Pending status without a corresponding
| visit_transfers row. In that case, do not expose an impossible receive
| action from the workspace.
|
*/

if (isset($hasPendingTransfer) && !$hasPendingTransfer) {

    $received = true;

}

$isClosedEncounter = in_array(
    (string)($visit['visit_status'] ?? ''),
    ['Completed', 'Cancelled'],
    true
);

?>

<div class="card encounter-header">

    <div class="encounter-header-left">

        <h2>

            <?= e($patient['first_name']) ?>

            <?= e($patient['last_name']) ?>

        </h2>

        <div class="encounter-meta">

            <span>

                <strong>

                    Hospital No:

                </strong>

                <?= e($patient['hospital_number']) ?>

            </span>

            <span>

                <strong>

                    Visit No:

                </strong>

                <?= e($visit['visit_number']) ?>

            </span>

        </div>

        <div class="encounter-meta">

            <span>

                <strong>

                    Gender:

                </strong>

                <?= e($patient['gender']) ?>

            </span>

            <span>

                <strong>

                    Age:

                </strong>

                <?= e($age) ?>

            </span>

            <span>

                <strong>

                    Phone:

                </strong>

                <?= e(

                    $patient['phone'] ?: '-'

                ) ?>

            </span>

        </div>

    </div>

    <div class="encounter-header-right">

        <?php if (!empty($canViewPatientChart)): ?>

            <a
                href="../medical_records/chart.php?patient=<?= (int)$patient['id'] ?>"
                class="btn-secondary">

                View Patient Chart

            </a>

        <?php endif; ?>

        <?php if (!$isClosedEncounter && !empty($canChangeEncounterStatus)): ?>
            <a
                href="complete.php?visit=<?= (int)$visit['id'] ?>"
                class="btn-primary">

                Complete Visit

            </a>
        <?php endif; ?>

        <div class="status-badge <?= e($statusClass) ?>">

            <?= e($visit['visit_status']) ?>

        </div>

        <table class="summary-table">

            <tr>

                <th>

                    Visit Type

                </th>

                <td>

                    <?= e($visit['visit_type']) ?>

                </td>

            </tr>

            <tr>

                <th>

                    Current Department

                </th>

                <td>

                    <?= e(

                        $visit['department_name']

                        ?? 'Not Assigned'

                    ) ?>

                </td>

            </tr>

            <tr>

                <th>

                    Attending Doctor

                </th>

                <td>

                    <?= e(

                        $visit['doctor_name']

                        ?? 'Not Assigned'

                    ) ?>

                </td>

            </tr>

            <tr>

                <th>

                    Visit Date

                </th>

                <td>

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

                </td>

            </tr>

        </table>

    </div>

</div>

<?php if (!$received) : ?>

<div class="card alert-warning encounter-workflow-banner">

    <h3>

        ⏳ Patient Awaiting Department Reception

    </h3>

    <p>

        This patient has been transferred to

        <strong>

            <?= e(

                $visit['department_name']

            ) ?>

        </strong>

        but has not yet been officially received.

    </p>

    <p>

        Clinical activities remain locked until the
        patient is received by the department.

    </p>

    <a

        href="receive.php?visit=<?= (int)$visit['id'] ?>"

        class="btn-primary">

        Receive Patient

    </a>

</div>

<?php else : ?>

<div class="card encounter-workflow-banner">

    <h3>

        ✅ Patient Received

    </h3>

    <table class="summary-table">

        <tr>

            <th>

                Current Department

            </th>

            <td>

                <?= e(

                    $visit['department_name']

                ) ?>

            </td>

        </tr>

        <tr>

            <th>

                Received By

            </th>

            <td>

                <?= e(

                    $visit['current_department_received_by_name']

                    ?? 'Unknown'

                ) ?>

            </td>

        </tr>

        <tr>

            <th>

                Received At

            </th>

            <td>

                <?= !empty(

                    $visit['current_department_received_at']

                )

                    ? e(

                        date(

                            'd M Y h:i A',

                            strtotime(

                                $visit['current_department_received_at']

                            )

                        )

                    )

                    : '-' ?>

            </td>

        </tr>

    </table>

</div>

<?php endif; ?>
