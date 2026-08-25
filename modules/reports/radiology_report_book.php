<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$permissionService->canViewClinicalReports($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to view clinical reports.');
}

$filters = reportsDateFilters();
$departments = $dashboardService->listReportDepartments();
$report = $dashboardService->getRadiologyReportBook($filters);
$dashboardService->recordReportView((int)($currentUser['id'] ?? 0), 'REPORT_VIEWED');

$pageTitle = 'Radiology Report Book';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Radiology Report Book</h1>
            <p>Printable register of Radiology studies and reports.</p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Radiology Report Book</button>
            <a class="btn-secondary" href="index.php">Reports</a>
        </div>
    </div>

    <?php reportsFilterForm($filters, $departments, ['status' => true, 'status_values' => ['Requested','In Progress','Completed','Cancelled']]); ?>

    <div class="card">
        <h3>Summary</h3>
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Requests</span> <span class="summary-value"><?= (int)($report['summary']['request_count'] ?? 0) ?></span></div>
            <div class="summary-item"><span class="summary-label">Reported</span> <span class="summary-value"><?= (int)($report['summary']['reported_count'] ?? 0) ?></span></div>
            <div class="summary-item"><span class="summary-label">Completed</span> <span class="summary-value"><?= (int)($report['summary']['completed_count'] ?? 0) ?></span></div>
        </div>
    </div>

    <div class="card">
        <h3>Report Book</h3>
        <?php if (empty($report['rows'])): ?>
            <p class="text-muted">No Radiology requests found for the selected filters.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Hospital No.</th>
                            <th>Visit No.</th>
                            <th>Study</th>
                            <th>Indication</th>
                            <th>Findings</th>
                            <th>Impression</th>
                            <th>Recommendation</th>
                            <th>Reported By</th>
                            <th>Status</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($report['rows'] as $row): ?>
                        <tr>
                            <td><?= e(!empty($row['created_at']) ? date('d M Y h:i A', strtotime((string)$row['created_at'])) : '-') ?></td>
                            <td><?= e((string)($row['patient_name'] ?? '-')) ?></td>
                            <td><?= e((string)($row['hospital_number'] ?? '-')) ?></td>
                            <td><?= e((string)($row['visit_number'] ?? '-')) ?></td>
                            <td><?= e((string)($row['study_requested'] ?? '-')) ?></td>
                            <td><?= e((string)($row['clinical_indication'] ?? '-')) ?></td>
                            <td><?= e((string)($row['findings'] ?? '-')) ?></td>
                            <td><?= e((string)($row['impression'] ?? '-')) ?></td>
                            <td><?= e((string)($row['recommendation'] ?? '-')) ?></td>
                            <td><?= e(trim((string)($row['performed_by_name'] ?? '')) ?: '-') ?></td>
                            <td><?= e((string)($row['status'] ?? '-')) ?></td>
                            <td class="no-print"><a class="btn-secondary btn-sm" href="../radiology/view.php?id=<?= (int)$row['id'] ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
