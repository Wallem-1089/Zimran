<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

reportsRequireAccess($permissionService, $currentUser);
$dashboardService->recordReportView((int)($currentUser['id'] ?? 0), 'REPORT_VIEWED');
$reportCards = [
    ['Patient / Encounter Activity', 'activity.php', 'Encounter counts by date, department, and status.', true],
    ['Emergency Register', 'emergency_register.php', 'Printable emergency book from Emergency encounters.', true],
    ['Clinical Activity', 'clinical.php', 'Consultation, nursing, diagnostic, theatre, and pharmacy counts.', $permissionService->canViewClinicalReports($currentUser)],
    ['Laboratory Report Book', 'laboratory_report_book.php', 'Printable Laboratory request/result register.', $permissionService->canViewClinicalReports($currentUser)],
    ['Radiology Report Book', 'radiology_report_book.php', 'Printable Radiology study/report register.', $permissionService->canViewClinicalReports($currentUser)],
    ['Theatre Operation Register', 'theatre_operation_register.php', 'Printable Theatre operation register.', $permissionService->canViewClinicalReports($currentUser)],
    ['Financial Summary', 'financial.php', 'Charges, invoices, payments, and outstanding balances.', $permissionService->canViewFinancialReports($currentUser)],
    ['Inventory Summary', 'inventory.php', 'Department balances and stock movement counts.', $permissionService->canViewInventoryReports($currentUser)],
];

$pageTitle = 'Reports';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Reports</h1>
            <p>Read-only summaries from existing hospital activity.</p>
        </div>
    </div>

    <div class="dashboard-columns">
        <?php foreach ($reportCards as $report): ?>
            <?php if (!$report[3]) { continue; } ?>
            <article class="card">
                <h3><?= e($report[0]) ?></h3>
                <p><?= e($report[2]) ?></p>
                <p><a class="btn-secondary" href="<?= e($report[1]) ?>">Open</a></p>
            </article>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
