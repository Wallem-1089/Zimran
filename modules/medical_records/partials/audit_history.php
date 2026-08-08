<?php

declare(strict_types=1);
?>

<div class="card">
    <h2>Patient Audit History</h2>
    <p>Audit records are immutable.</p>

    <?php if ($auditHistory === []): ?>
        <p>No patient-aware audit records are available.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>Department</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auditHistory as $audit): ?>
                        <tr>
                            <td><?= e($audit['created_at']) ?></td>
                            <td><?= e($audit['action']) ?></td>
                            <td><?= e(trim(($audit['first_name'] ?? '') . ' ' . ($audit['last_name'] ?? '')) ?: 'System') ?></td>
                            <td><?= e($audit['department_name'] ?? '-') ?></td>
                            <td><?= e($audit['description'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
