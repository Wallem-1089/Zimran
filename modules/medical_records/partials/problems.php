<?php

declare(strict_types=1);
?>
<section class="card">
    <div class="section-heading"><h2>Longitudinal Problem List</h2><?php if ($canManageProblemList): ?><a class="btn-primary" href="problems/create.php?patient=<?= (int)$patient['id'] ?><?= e($chartContextQuery) ?>">Add Problem</a><?php endif; ?></div>
    <?php if ($problems === []): ?><p class="text-muted">No longitudinal problems are recorded.</p><?php else: ?>
        <?php foreach (['Active', 'Inactive', 'Resolved'] as $status): $group = array_values(array_filter($problems, static fn (array $row): bool => ($row['clinical_status'] ?? '') === $status)); if ($group === []) { continue; } ?>
            <h3><?= e($status) ?> Problems</h3><table><thead><tr><th>Problem</th><th>Category</th><th>Severity</th><th>Verification</th><th></th></tr></thead><tbody>
            <?php foreach ($group as $problem): ?><tr><td><?= e($problem['problem_name']) ?><?= !empty($problem['confidential_hidden']) ? ' (details hidden)' : '' ?></td><td><?= e($problem['category']) ?></td><td><?= e($problem['severity']) ?></td><td><?= e($problem['verification_status']) ?></td><td><a href="problems/view.php?id=<?= (int)$problem['id'] ?><?= e($chartContextQuery) ?>">View</a></td></tr><?php endforeach; ?>
            </tbody></table>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
