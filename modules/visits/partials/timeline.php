<?php
$events = $visitService->getVisitTimeline((int)$visit['id']);
$eventCount = count($events);
$latestEvent = $events[0] ?? null;
$latestLabel = null;

if ($latestEvent && !empty($latestEvent['created_at'])) {
    $latestLabel = date('M j, Y g:i A', strtotime((string)$latestEvent['created_at']));
}
?>

<section class="card timeline-card">
    <details class="timeline-panel">
        <summary class="timeline-summary">
            <span class="timeline-summary-main">
                <span class="timeline-summary-title">Encounter Timeline</span>
                <span class="timeline-summary-subtitle">
                    <?php if ($eventCount > 0): ?>
                        <?= (int)$eventCount ?> event<?= $eventCount === 1 ? '' : 's' ?> recorded<?= $latestLabel ? ' - latest ' . e($latestLabel) : '' ?>
                    <?php else: ?>
                        No timeline events recorded yet
                    <?php endif; ?>
                </span>
            </span>
            <span class="timeline-toggle-text" aria-hidden="true">Expand</span>
        </summary>

        <div class="timeline-panel-body">
            <?php if (!empty($events)) : ?>
                <div class="timeline">
                    <?php foreach ($events as $event) : ?>
                        <?php
                        $eventType = (string)($event['type'] ?? '');
                        $eventClass = match ($eventType) {
                            'visit_created' => 'created',
                            'received' => 'received',
                            'transfer' => 'transfer',
                            'doctor_assignment' => 'assignment',
                            'completed' => 'completed',
                            'cancelled' => 'cancelled',
                            default => 'default',
                        };
                        ?>
                        <div class="timeline-item <?= e($eventClass) ?>">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h3><?= e((string)($event['title'] ?? 'Timeline Event')) ?></h3>
                                <?php if (!empty($event['description'])) : ?>
                                    <p><?= e((string)$event['description']) ?></p>
                                <?php endif; ?>
                                <div class="timeline-meta">
                                    <span><strong>Performed By:</strong> <?= e((string)($event['performed_by_name'] ?? 'System')) ?></span>
                                    <span><strong>Time:</strong> <?= e(date('M j, Y g:i A', strtotime((string)$event['created_at']))) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="timeline-item future">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h3>Future Clinical Activities</h3>
                            <p>Consultation, clinical notes, orders, and results will appear here as those modules are enabled.</p>
                            <div class="timeline-meta">
                                <span><strong>Status:</strong> Planned for future phases</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <div class="empty-state">
                    <p>No timeline events have been recorded for this encounter yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </details>
</section>
