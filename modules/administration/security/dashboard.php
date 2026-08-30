<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireSecurityAdministrator();
$pdo->beginTransaction();
$securityAuditService->log(
    (int)$currentUser['id'],
    null,
    'Security',
    'SECURITY_REPORT_VIEWED',
    'Viewed the security dashboard.',
    $_SESSION['active_department_id'] ?? null,
    'INFO',
    'SECURITY_REPORT_VIEWED'
);
$pdo->commit();
$summary = $securityAuditService->securitySummary()['data'] ?? [];
$recent = $securityAuditService->search(['module' => 'Security'], 1, 10)['data'] ?? [];
$pageTitle = 'Security Dashboard';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Security Dashboard</h2>
        <div class="stats">
            <div class="card"><h3>Active Sessions</h3><h2><?= (int)($summary['active_sessions'] ?? 0) ?></h2></div>
            <div class="card"><h3>Failed Logins Today</h3><h2><?= (int)($summary['failed_logins_today'] ?? 0) ?></h2></div>
            <div class="card"><h3>Locked Accounts</h3><h2><?= (int)($summary['locked_accounts'] ?? 0) ?></h2></div>
            <div class="card"><h3>Password Resets Today</h3><h2><?= (int)($summary['password_resets_today'] ?? 0) ?></h2></div>
        </div>
        <p><a href="active_sessions.php">Active Sessions</a> | <a href="audit_logs.php">Audit Viewer</a> | <a href="login_history.php">Login History</a> | <a href="account_lockouts.php">Account Lockouts</a> | <a href="password_history.php">Password History</a> | <a href="database_backup.php">Database Safety</a></p>
        <h3>Recent Security Events</h3>
        <table><thead><tr><th>Time</th><th>Action</th><th>Description</th><th>Severity</th></tr></thead><tbody>
        <?php foreach ($recent as $event): ?><tr><td><?= e($event['created_at']) ?></td><td><?= e($event['action']) ?></td><td><?= e($event['description']) ?></td><td><?= e($event['severity']) ?></td></tr><?php endforeach; ?>
        </tbody></table>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
