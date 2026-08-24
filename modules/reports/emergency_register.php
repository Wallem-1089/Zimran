<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

reportsRequireAccess($permissionService, $currentUser);

$filters = reportsDateFilters();
$departments = $dashboardService->listReportDepartments();
$report = $dashboardService->getEmergencyRegister($filters);
$dashboardService->recordReportView((int)($currentUser['id'] ?? 0), 'REPORT_VIEWED');

$pageTitle = 'Emergency Register';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Emergency Register</h1>
            <p>Printable emergency book from existing Emergency encounters.</p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Emergency Register</button>
            <a class="btn-secondary" href="index.php">Reports</a>
        </div>
    </div>

    <?php reportsFilterForm($filters, $departments, ['status' => true]); ?>

    <div class="card">
        <h3>Summary</h3>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Emergency Encounters</span> <span class="summary-value"><?= (int)($report['summary']['emergency_count'] ?? 0) ?></span></div>
            <div class="summary-item"><span class="summary-label">Completed</span> <span class="summary-value"><?= (int)($report['summary']['completed_count'] ?? 0) ?></span></div>
            <div class="summary-item"><span class="summary-label">Active</span> <span class="summary-value"><?= (int)($report['summary']['active_count'] ?? 0) ?></span></div>
        </div>
    </div>

    <div class="card">
        <h3>Emergency Book</h3>
        <?php if (empty($report['rows'])): ?>
            <p class="text-muted">No emergency encounters found for the selected filters.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Arrival</th>
                            <th>Patient</th>
                            <th>Hospital No.</th>
                            <th>Visit No.</th>
                            <th>Presenting Complaint</th>
                            <th>Department</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Completed / Discharged</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['rows'] as $row): ?>
                            <tr>
                                <td><?= e(!empty($row['visit_date']) ? date('d M Y h:i A', strtotime((string)$row['visit_date'])) : '-') ?></td>
                                <td><?= e((string)($row['patient_name'] ?? '-')) ?></td>
                                <td><?= e((string)($row['hospital_number'] ?? '-')) ?></td>
                                <td><?= e((string)($row['visit_number'] ?? '-')) ?></td>
                                <td><?= e((string)($row['presenting_complaint'] ?? '-')) ?></td>
                                <td><?= e((string)($row['department_name'] ?? 'Not Assigned')) ?></td>
                                <td><?= e(trim((string)($row['doctor_name'] ?? '')) ?: 'Not Assigned') ?></td>
                                <td><?= e((string)($row['visit_status'] ?? '-')) ?></td>
                                <td><?= e(!empty($row['completed_at']) ? date('d M Y h:i A', strtotime((string)$row['completed_at'])) : '-') ?></td>
                                <td class="no-print">
                                    <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$row['id'] ?>">Open Encounter</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted">Showing up to 500 emergency encounters. Narrow the date range if more detail is needed.</p>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
