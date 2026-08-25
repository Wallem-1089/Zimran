<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

reportsRequireAccess($permissionService, $currentUser);
$filters = reportsDateFilters();
$departments = $dashboardService->listReportDepartments();
$report = $dashboardService->getPatientEncounterActivity($filters);
$dashboardService->recordReportView((int)($currentUser['id'] ?? 0), 'REPORT_VIEWED');

$pageTitle = 'Patient / Encounter Activity';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div><h1>Patient / Encounter Activity</h1><p>Encounter volume by date range and department.</p></div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Activity Report</button>
            <a class="btn-secondary" href="index.php">Reports</a>
        </div>
    </div>
    <?php reportsFilterForm($filters, $departments, ['status' => true]); ?>
    <div class="card">
        <h3>Summary</h3>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Encounters</span> <span class="summary-value"><?= (int)($report['summary']['encounter_count'] ?? 0) ?></span></div>
            <div class="summary-item"><span class="summary-label">Completed</span> <span class="summary-value"><?= (int)($report['summary']['completed_count'] ?? 0) ?></span></div>
            <div class="summary-item"><span class="summary-label">Active</span> <span class="summary-value"><?= (int)($report['summary']['active_count'] ?? 0) ?></span></div>
        </div>
    </div>
    <div class="card">
        <h3>Department Activity</h3>
        <?php if (empty($report['by_department'])): ?>
            <p class="text-muted">No encounter activity found.</p>
        <?php else: ?>
            <table class="table"><thead><tr><th>Department</th><th>Encounters</th><th>Completed</th></tr></thead><tbody>
            <?php foreach ($report['by_department'] as $row): ?>
                <tr><td><?= e((string)$row['department_name']) ?></td><td><?= (int)$row['encounter_count'] ?></td><td><?= (int)$row['completed_count'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
