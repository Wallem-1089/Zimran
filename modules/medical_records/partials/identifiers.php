<?php

declare(strict_types=1);
?>

<?php if ($duplicateWarning): ?>
    <div class="card chart-foundation-notice">
        <strong>Possible duplicate record requires review.</strong>
        This chart has an unresolved <?= e($duplicateWarning['classification']) ?> case.
        <?php if ($permissionService->canViewDuplicateCandidates($currentUser)): ?>
            <a href="mpi/candidate.php?id=<?= (int)$duplicateWarning['id'] ?>">Review case</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="page-header">
        <div>
            <h2>Patient Identifiers</h2>
            <p>The hospital number remains the authoritative local identifier.</p>
        </div>
        <?php if ($canManageIdentifiers): ?>
            <a class="btn-primary" href="identifiers/create.php?patient=<?= (int)$patient['id'] ?>">
                Add Identifier
            </a>
        <?php endif; ?>
    </div>

    <div class="chart-detail-grid">
        <div><span>Hospital Number</span><strong><?= e($patient['hospital_number']) ?></strong></div>
    </div>

    <?php if ($identifiers === []): ?>
        <p class="text-muted">No alternate identifiers are recorded.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Type</th><th>Value</th><th>Authority</th><th>Status</th><th>Validity</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($identifiers as $identifier): ?>
                    <tr>
                        <td><?= e($identifier['identifier_type']) ?><?= $identifier['is_primary'] ? ' (Primary)' : '' ?></td>
                        <td><?= e($identifier['masked_value']) ?></td>
                        <td><?= e($identifier['issuing_authority'] ?? '-') ?></td>
                        <td><?= $identifier['is_active'] ? e($identifier['verification_status']) : 'Inactive' ?></td>
                        <td><?= e($identifier['issue_date'] ?? '-') ?> – <?= e($identifier['expiry_date'] ?? '-') ?></td>
                        <td><a href="identifiers/view.php?id=<?= (int)$identifier['id'] ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
