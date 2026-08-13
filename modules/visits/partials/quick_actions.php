<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Required Variables
|--------------------------------------------------------------------------
|
| Expected:
| $visit
|
*/

if (!isset($visit)) {

    return;

}

?>

<div class="card">

    <h2>

        Quick Actions

    </h2>

    <div class="patient-actions-grid">

        <!-- Patient Profile -->

        <a
            href="../patients/view.php?id=<?= (int)$visit['patient_id'] ?>"
            class="action-card action-primary">

            <div class="action-icon">

                👤

            </div>

            <div class="action-content">

                <strong>

                    Patient Profile

                </strong>

                <span>

                    View complete patient information

                </span>

            </div>

        </a>

        <!-- Edit Patient -->

        <a
            href="../patients/edit.php?id=<?= (int)$visit['patient_id'] ?>"
            class="action-card">

            <div class="action-icon">

                ✏️

            </div>

            <div class="action-content">

                <strong>

                    Edit Patient

                </strong>

                <span>

                    Update patient information

                </span>

            </div>

        </a>

        <!-- Encounter History -->

        <a
            href="../patients/history.php?id=<?= (int)$visit['patient_id'] ?>"
            class="action-card">

            <div class="action-icon">

                📜

            </div>

            <div class="action-content">

                <strong>

                    Encounter History

                </strong>

                <span>

                    View all patient encounters

                </span>

            </div>

        </a>

        <!-- Search Patients -->

        <a
            href="../patients/search.php"
            class="action-card">

            <div class="action-icon">

                🔍

            </div>

            <div class="action-content">

                <strong>

                    Search Patients

                </strong>

                <span>

                    Find another patient

                </span>

            </div>

        </a>

    </div>

</div>

<?php if (($notificationTablesReady ?? false) && !in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true) && isset($departments)): ?>
<div class="card">
    <h2>Notify Department</h2>
    <p class="text-muted">
        Request attention from another department without transferring encounter ownership.
    </p>

    <form method="post" action="notify_department_save.php" class="form-grid">
        <?= csrfField() ?>
        <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">

        <div class="form-group">
            <label for="to_department_id">Destination Department</label>
            <select id="to_department_id" name="to_department_id" required>
                <option value="">Select department</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= (int)$department['id'] ?>">
                        <?= e((string)$department['department_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="notification_reason">Reason</label>
            <textarea id="notification_reason" name="reason" rows="3" required></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Send Notification</button>
        </div>
    </form>
</div>
<?php endif; ?>
