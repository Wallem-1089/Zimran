<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$permissionService->canViewFinancialReports($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to view financial reports.');
}
$filters = reportsDateFilters();
$report = $dashboardService->getFinancialReport($filters);
$registrationPayments = 0.0;
try {
    $tableCheck = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = :table'
    );
    $tableCheck->execute([':table' => 'patient_registration_billing_requests']);
    if ((int)$tableCheck->fetchColumn() > 0) {
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM patient_registration_billing_requests
             WHERE status = \'Paid\'
               AND cleared_at BETWEEN :from AND :to'
        );
        $stmt->execute([
            ':from' => $report['filters']['from'] ?? ($filters['date_from'] . ' 00:00:00'),
            ':to' => $report['filters']['to'] ?? ($filters['date_to'] . ' 23:59:59'),
        ]);
        $registrationPayments = (float)$stmt->fetchColumn();
    }
} catch (Throwable) {
    $registrationPayments = 0.0;
}
$totalPayments = (float)($report['payments'] ?? 0);
$encounterPayments = max(0.0, $totalPayments - $registrationPayments);
$dashboardService->recordReportView((int)($currentUser['id'] ?? 0), 'FINANCIAL_REPORT_VIEWED');

$pageTitle = 'Financial Summary';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div><h1>Financial Summary</h1><p>Posted encounter billing plus cleared registration payments. Accounts catalogue prices are not recalculated here.</p></div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Financial Summary</button>
            <a class="btn-secondary" href="index.php">Reports</a>
        </div>
    </div>
    <?php reportsFilterForm($filters, []); ?>
    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Active Charges</span> <span class="summary-value"><?= e(number_format((float)$report['charges'], 2)) ?></span></div>
            <div class="summary-item"><span class="summary-label">Invoice Value</span> <span class="summary-value"><?= e(number_format((float)$report['invoices'], 2)) ?></span></div>
            <div class="summary-item"><span class="summary-label">Encounter Payments</span> <span class="summary-value"><?= e(number_format($encounterPayments, 2)) ?></span></div>
            <div class="summary-item"><span class="summary-label">Registration Payments</span> <span class="summary-value"><?= e(number_format($registrationPayments, 2)) ?></span></div>
            <div class="summary-item"><span class="summary-label">Payments Received</span> <span class="summary-value"><?= e(number_format($totalPayments, 2)) ?></span></div>
            <div class="summary-item"><span class="summary-label">Open Invoices</span> <span class="summary-value"><?= (int)$report['open_invoices'] ?></span></div>
            <div class="summary-item"><span class="summary-label">Outstanding Balance</span> <span class="summary-value"><?= e(number_format((float)$report['outstanding_balance'], 2)) ?></span></div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
