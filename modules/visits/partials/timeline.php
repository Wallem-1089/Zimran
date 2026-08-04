<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Required Variables
|--------------------------------------------------------------------------
|
| Expected:
| $visit
| $visitService
|
*/

if (

    !isset($visit) ||

    !isset($visitService)

) {

    return;

}

/*
|--------------------------------------------------------------------------
| Timeline
|--------------------------------------------------------------------------
*/

$events = $visitService->getVisitTimeline(

    (int)$visit['id']

);

?>

<div class="card">

    <div class="card-header">

        <h2>

            Encounter Timeline

        </h2>

    </div>

    <?php if (!empty($events)) : ?>

        <div class="timeline">

            <?php foreach ($events as $event) : ?>

                <?php

                /*
                |--------------------------------------------------------------------------
                | Timeline Marker
                |--------------------------------------------------------------------------
                */

                $marker = match ($event['type']) {

                    'creation' => 'success',

                    'transfer' => 'info',

                    'consultation' => 'doctor',

                    'nursing' => 'nursing',

                    'laboratory' => 'laboratory',

                    'radiology' => 'radiology',

                    'pharmacy' => 'pharmacy',

                    'billing' => 'accounts',

                    'physiotherapy' => 'physiotherapy',

                    'theatre' => 'theatre',

                    'document' => 'default',

                    'note' => 'default',

                    default => 'default'

                };

                ?>

                <div class="timeline-item">

                    <div class="timeline-marker timeline-<?= e($marker) ?>">

                    </div>

                    <div class="timeline-content">

                        <div class="timeline-title">

                            <?= e($event['title']) ?>

                        </div>

                        <div class="timeline-description">

                            <?= e($event['description']) ?>

                        </div>

                        <?php if (!empty($event['performed_by'])) : ?>

                            <div class="timeline-meta">

                                <strong>

                                    Performed By:

                                </strong>

                                <?= e($event['performed_by']) ?>

                            </div>

                        <?php endif; ?>

                        <?php if (!empty($event['department'])) : ?>

                            <div class="timeline-meta">

                                <strong>

                                    Department:

                                </strong>

                                <?= e($event['department']) ?>

                            </div>

                        <?php endif; ?>

                        <?php if (!empty($event['transfer_type'])) : ?>

                            <div class="timeline-meta">

                                <strong>

                                    Transfer Type:

                                </strong>

                                <?= e($event['transfer_type']) ?>

                            </div>

                        <?php endif; ?>

                        <?php if (!empty($event['remarks'])) : ?>

                            <div class="timeline-meta">

                                <strong>

                                    Remarks:

                                </strong>

                                <?= nl2br(e($event['remarks'])) ?>

                            </div>

                        <?php endif; ?>

                        <div class="timeline-time">

                            <?= !empty($event['created_at'])

                                ? e(

                                    date(

                                        'd M Y, h:i A',

                                        strtotime(

                                            $event['created_at']

                                        )

                                    )

                                )

                                : '-' ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

            <div class="timeline-item">

                <div class="timeline-marker timeline-pending">

                </div>

                <div class="timeline-content">

                    <div class="timeline-title">

                        Future Clinical Activities

                    </div>

                    <div class="timeline-description">

                        Consultation, Nursing,
                        Laboratory,
                        Radiology,
                        Pharmacy,
                        Billing,
                        Physiotherapy,
                        Theatre,
                        Documents and Clinical Notes
                        will automatically appear here
                        as they are completed during
                        this encounter.

                    </div>

                </div>

            </div>

        </div>

    <?php else : ?>

        <div class="empty-state">

            <p>

                No timeline events are available for this encounter.

            </p>

        </div>

    <?php endif; ?>

</div>