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
$enableWritingMode = isset($permissionService)
    && method_exists($permissionService, 'canUseConsultationHandwriting')
    && $permissionService->canUseConsultationHandwriting($currentUser ?? null);

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

    <form method="post" action="notify_department_save.php" class="form-grid" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
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
            <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Department Notification Entry Mode'); ?>
            <?php hmsRenderHandwritingTextarea('reason', 'Reason', '', 3, true, $enableWritingMode); ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Send Notification</button>
        </div>
    </form>
    <?php hmsRenderHandwritingScript($enableWritingMode); ?>
</div>
<?php endif; ?>

<?php if (($userNotificationTablesReady ?? false) && !in_array((string)($visit['visit_status'] ?? ''), ['Completed', 'Cancelled'], true) && isset($notificationUsers)): ?>
<div class="card">
    <h2>Notify User</h2>
    <p class="text-muted">
        Send a direct attention request to a specific staff account. This does not transfer encounter ownership.
    </p>

    <form method="post" action="notify_user_save.php" class="form-grid" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
        <?= csrfField() ?>
        <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">

        <div class="form-group">
            <label for="to_user_id">User Account</label>
            <select id="to_user_id" name="to_user_id" required>
                <option value="">Select user</option>
                <?php foreach ($notificationUsers as $notificationUser): ?>
                    <?php if ((int)$notificationUser['id'] === (int)($currentUser['id'] ?? 0)) { continue; } ?>
                    <option value="<?= (int)$notificationUser['id'] ?>">
                        <?= e(trim((string)$notificationUser['first_name'] . ' ' . (string)$notificationUser['last_name'])) ?>
                        — <?= e((string)$notificationUser['role_name']) ?>
                        / <?= e((string)$notificationUser['department_name']) ?>
                        (<?= e((string)$notificationUser['employee_id']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <?php hmsRenderHandwritingToolbar($enableWritingMode, 'User Notification Entry Mode'); ?>
            <?php hmsRenderHandwritingTextarea('message', 'Message', '', 3, true, $enableWritingMode); ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Send User Notification</button>
        </div>
    </form>
    <?php hmsRenderHandwritingScript($enableWritingMode); ?>
</div>
<?php elseif (!($userNotificationTablesReady ?? false)): ?>
<div class="card">
    <h2>Notify User</h2>
    <p class="text-muted">User notification tables are not available yet. Apply Migration 041 to enable this section.</p>
</div>
<?php endif; ?>
