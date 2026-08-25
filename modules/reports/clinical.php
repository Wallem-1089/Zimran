<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$permissionService->canViewClinicalReports($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to view clinical reports.');
}
$filters = reportsDateFilters();
$departments = $dashboardService->listReportDepartments();
$report = $dashboardService->getClinicalActivityReport($filters);
$dashboardService->recordReportView((int)($currentUser['id'] ?? 0), 'REPORT_VIEWED');

$pageTitle = 'Clinical Activity';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div><h1>Clinical Activity</h1><p>Aggregate clinical workload. Narrative clinical content is not shown.</p></div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Clinical Activity</button>
            <a class="btn-secondary" href="index.php">Reports</a>
        </div>
    </div>
    <?php reportsFilterForm($filters, $departments); ?>
    <div class="card">
        <?php if (empty($report['items'])): ?>
            <p class="text-muted">No clinical activity found.</p>
        <?php else: ?>
            <table class="table"><thead><tr><th>Area</th><th>Total</th></tr></thead><tbody>
            <?php foreach ($report['items'] as $row): ?>
                <tr><td><?= e((string)$row['label']) ?></td><td><?= (int)$row['total'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
