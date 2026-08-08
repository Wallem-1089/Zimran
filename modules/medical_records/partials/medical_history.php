<?php

declare(strict_types=1);
?>
<section class="card">
    <div class="section-heading"><h2>Structured Medical History</h2><?php if ($canManageMedicalHistory): ?><a class="btn-primary" href="history/create.php?patient=<?= (int)$patient['id'] ?><?= e($chartContextQuery) ?>">Add History</a><?php endif; ?></div>
    <?php if ($medicalHistory === []): ?><p class="text-muted">No structured medical history is recorded.</p><?php else: ?>
        <?php $types = array_values(array_unique(array_column($medicalHistory, 'history_type'))); foreach ($types as $type): ?><h3><?= e($type) ?></h3><table><thead><tr><th>Title</th><th>Date</th><th>Status</th><th>Verified</th><th></th></tr></thead><tbody>
        <?php foreach ($medicalHistory as $entry): if ($entry['history_type'] !== $type) { continue; } ?><tr><td><?= e($entry['title']) ?><?= !empty($entry['confidential_hidden']) ? ' (details hidden)' : '' ?></td><td><?= e($entry['event_date'] ?? 'Unknown') ?></td><td><?= e($entry['status']) ?></td><td><?= $entry['verified_at'] ? 'Yes' : 'No' ?></td><td><a href="history/view.php?id=<?= (int)$entry['id'] ?><?= e($chartContextQuery) ?>">View</a></td></tr><?php endforeach; ?>
        </tbody></table><?php endforeach; ?>
    <?php endif; ?>
</section>
