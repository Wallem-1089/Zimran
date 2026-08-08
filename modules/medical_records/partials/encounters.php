<?php

declare(strict_types=1);
?>

<div class="card">
    <h2>Encounter History</h2>
    <?php if ($encounters === []): ?>
        <p>No encounters have been recorded for this patient.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Visit Number</th>
                        <th>Date</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($encounters as $encounter): ?>
                        <tr>
                            <td><?= e($encounter['visit_number']) ?></td>
                            <td><?= e($encounter['visit_date']) ?></td>
                            <td><?= e($encounter['department_name'] ?? 'Unassigned') ?></td>
                            <td><?= e($encounter['visit_status']) ?></td>
                            <td>
                                <a href="../visits/workspace.php?id=<?= (int)$encounter['id'] ?>"
                                    class="btn-secondary">Open Workspace</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
