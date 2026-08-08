<?php

declare(strict_types=1);
?>

<div class="card">
    <h2>Demographic History</h2>
    <p>Entries are append-only and cannot be edited or deleted.</p>

    <?php if ($demographicHistory === []): ?>
        <p>No demographic amendments have been recorded.</p>
    <?php else: ?>
        <?php foreach ($demographicHistory as $entry): ?>
            <section class="history-entry">
                <h3>Version <?= (int)$entry['version_no'] ?></h3>
                <p>
                    <?= e($entry['changed_by_name']) ?>
                    &middot; <?= e($entry['created_at']) ?>
                </p>
                <p><strong>Reason:</strong> <?= e($entry['reason']) ?></p>
                <p>
                    <strong>Changed fields:</strong>
                    <?= e(implode(', ', $entry['changed_fields'])) ?>
                </p>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
