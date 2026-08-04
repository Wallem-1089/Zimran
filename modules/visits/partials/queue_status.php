<?php

declare(strict_types=1);

if (!isset($visit) || !isset($visitService)) {
    return;
}

$queueEntry = $visitService->getQueueEntryForVisit(
    (int)$visit['id']
);

?>

<div class="card">

    <div class="card-header">

        <h2>Department Queue</h2>

    </div>

    <?php if ($queueEntry): ?>

        <div class="summary-table">

            <div class="review-item">

                <div class="review-label">Queue Status</div>

                <div class="review-value">

                    <?= e($queueEntry['queue_status']) ?>

                </div>

            </div>

            <div class="review-item">

                <div class="review-label">Department</div>

                <div class="review-value">

                    <?= e($queueEntry['department_name']) ?>

                </div>

            </div>

            <div class="review-item">

                <div class="review-label">Position</div>

                <div class="review-value">

                    <?= $queueEntry['position'] !== null
                        ? (int)$queueEntry['position']
                        : '-' ?>

                </div>

            </div>

            <?php if (!empty($queueEntry['assigned_user_name'])): ?>

                <div class="review-item">

                    <div class="review-label">Assigned To</div>

                    <div class="review-value">

                        <?= e($queueEntry['assigned_user_name']) ?>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    <?php elseif (!in_array(
        $visit['visit_status'],
        ['Completed', 'Cancelled'],
        true
    )): ?>

        <div class="alert-info">

            This encounter is not currently in a department queue.

        </div>

    <?php else: ?>

        <div class="alert-info">

            Queue actions are disabled for this closed encounter.

        </div>

    <?php endif; ?>

</div>

