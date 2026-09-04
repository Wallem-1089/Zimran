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

/*
|--------------------------------------------------------------------------
| Current Status
|--------------------------------------------------------------------------
*/

$status = $visit['visit_status'] ?? 'Waiting';

$statusClass = match ($status) {

    'Waiting'        => 'status-waiting',
    'Reception'      => 'status-reception',
    'Records'        => 'status-records',
    'Nursing'        => 'status-nursing',
    'Doctor'         => 'status-doctor',
    'Laboratory'     => 'status-laboratory',
    'X-Ray'          => 'status-radiology',
    'Pharmacy'       => 'status-pharmacy',
    'Physiotherapy'  => 'status-physiotherapy',
    'Theatre'        => 'status-theatre',
    'Accounts'       => 'status-accounts',
    'Completed'      => 'status-completed',
    'Cancelled'      => 'status-cancelled',

    default          => 'status-default'

};

/*
|--------------------------------------------------------------------------
| Workflow
|--------------------------------------------------------------------------
*/

$workflow = [

    'Waiting'       => 'Reception',
    'Reception'     => 'Records',
    'Records'       => 'Nursing',
    'Nursing'       => 'Doctor',
    'Doctor'        => 'Laboratory',
    'Laboratory'    => 'Doctor',
    'X-Ray'         => 'Doctor',
    'Pharmacy'      => 'Accounts',
    'Physiotherapy' => 'Doctor',
    'Theatre'       => 'Doctor',
    'Accounts'      => 'Completed'

];

$nextStatus = $workflow[$status] ?? null;

$isClosedEncounter = in_array(
    $status,
    ['Completed', 'Cancelled'],
    true
);

$canChangeStatus = !isset($permissionService)
    || $permissionService->canChangeEncounterStatus(
        $visit,
        $currentUser ?? null
    );

$canTransfer = !isset($permissionService)
    || $permissionService->canTransferEncounter(
        $visit,
        $currentUser ?? null
    );

$canCancelEncounter = isset($permissionService)
    && $permissionService->canCancelEncounter(
        $visit,
        $currentUser ?? null
    );

$canReopenEncounter = isset($permissionService)
    && $permissionService->canReopenEncounter(
        $visit,
        $currentUser ?? null
    );

$canAssignDoctor = !isset($permissionService)
    || $permissionService->canAssignDoctor(
        $visit,
        $currentUser ?? null
    );

?>

<div class="card">

    <div class="card-header">

        <h2>Encounter Status</h2>

    </div>

    <div class="status-container">

        <div class="status-row">

            <span class="status-label">

                Visit Number

            </span>

            <span class="status-value">

                <?= e($visit['visit_number']) ?>

            </span>

        </div>

        <div class="status-row">

            <span class="status-label">

                Current Status

            </span>

            <span class="status-badge <?= e($statusClass) ?>">

                <?= e($status) ?>

            </span>

        </div>

        <div class="status-row">

            <span class="status-label">

                Visit Type

            </span>

            <span class="status-value">

                <?= e($visit['visit_type']) ?>

            </span>

        </div>

        <div class="status-row">

            <span class="status-label">

                Encounter Owner / Current Department

            </span>

            <span class="status-value">

                <?= e($visit['department_name'] ?? 'Not Assigned') ?>

            </span>

        </div>

        <div class="status-row">

            <span class="status-label">

                Attending Doctor

            </span>

            <span class="status-value">

                <?= e($visit['doctor_name'] ?? 'Not Assigned') ?>

            </span>

        </div>

        <div class="status-row">

            <span class="status-label">

                Visit Date

            </span>

            <span class="status-value">

                <?= e(date(
                    'd M Y h:i A',
                    strtotime($visit['visit_date'])
                )) ?>

            </span>

        </div>

        <hr>

        <div class="status-row">

            <span class="status-label">

                Consultation

            </span>

            <span class="status-value">

                Pending

            </span>

        </div>

        <div class="status-row">

            <span class="status-label">

                Nursing

            </span>

            <span class="status-value">

                Pending

            </span>

        </div>

        <div class="status-row">

            <span class="status-label">

                Laboratory

            </span>

            <span class="status-value">

                No Requests

            </span>

        </div>

        <div class="status-row">

            <span class="status-label">

                Radiology

            </span>

            <span class="status-value">

                No Requests

            </span>

        </div>

        <div class="status-row">

            <span class="status-label">

                Pharmacy

            </span>

            <span class="status-value">

                No Medications

            </span>

        </div>

        <div class="status-row">

            <span class="status-label">

                Billing

            </span>

            <span class="status-value">

                ₦0.00 Outstanding

            </span>

        </div>

        <hr>

        <h3>

            Workflow Actions

        </h3>

        <?php if ($nextStatus !== null && $canChangeStatus): ?>

            <?php if ($nextStatus === 'Completed'): ?>

                <a
                    href="complete.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-primary">

                    Complete Encounter

                </a>

            <?php else: ?>

                <form
                    method="POST"
                    action="change_status.php"
                    style="margin-bottom:10px;">

                    <?= csrfField() ?>

                    <input
                        type="hidden"
                        name="visit_id"
                        value="<?= (int)$visit['id'] ?>">

                    <input
                        type="hidden"
                        name="visit_status"
                        value="<?= e($nextStatus) ?>">

                    <button
                        type="submit"
                        class="btn-primary">

                        Move to <?= e($nextStatus) ?>

                    </button>

                </form>

            <?php endif; ?>

        <?php elseif ($status === 'Completed'): ?>

            <div class="alert-success">

                This encounter has been completed.

            </div>

            <?php if ($canReopenEncounter): ?>

                <a
                    href="reopen.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-secondary">

                    Reopen Encounter

                </a>

            <?php endif; ?>

        <?php elseif ($status === 'Cancelled'): ?>

            <div class="alert-danger">

                This encounter has been cancelled.

            </div>

        <?php endif; ?>

        <?php if (!$isClosedEncounter && $canCancelEncounter): ?>

            <form
                method="POST"
                action="change_status.php"
                class="cancel-encounter-form"
                onsubmit="return confirm('Cancel this encounter? This will make clinical encounter sections read-only.');">

                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="visit_id"
                    value="<?= (int)$visit['id'] ?>">

                <input
                    type="hidden"
                    name="visit_status"
                    value="Cancelled">

                <button
                    type="submit"
                    class="btn-danger">

                    Cancel Encounter

                </button>

            </form>

        <?php endif; ?>

        <?php if (!$isClosedEncounter && ($canTransfer || $canAssignDoctor)): ?>

        <div class="form-actions encounter-workflow-actions">

            <?php if ($canTransfer): ?>

                <a
                    href="transfer.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-secondary">

                    Transfer Department

                </a>

            <?php endif; ?>

            <?php if ($canAssignDoctor): ?>

                <a
                    href="assign_doctor.php?visit=<?= (int)$visit['id'] ?>"
                    class="btn-secondary">

                    Assign Doctor

                </a>

            <?php endif; ?>

        </div>

        <?php endif; ?>

    </div>

</div>
