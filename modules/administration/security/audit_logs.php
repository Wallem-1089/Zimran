<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireSecurityAdministrator();
$pdo->beginTransaction();
$securityAuditService->log(
    (int)$currentUser['id'],
    null,
    'Security',
    'AUDIT_LOG_VIEWED',
    'Viewed the audit log viewer.',
    $_SESSION['active_department_id'] ?? null,
    'INFO',
    'AUDIT_LOG_VIEWED'
);
$pdo->commit();
$filters = [
    'module' => trim((string)($_GET['module'] ?? '')),
    'action' => trim((string)($_GET['action'] ?? '')),
    'event_type' => trim((string)($_GET['event_type'] ?? '')),
    'user_id' => (int)($_GET['user_id'] ?? 0),
    'visit_id' => (int)($_GET['visit_id'] ?? 0),
    'department_id' => (int)($_GET['department_id'] ?? 0),
    'severity' => trim((string)($_GET['severity'] ?? '')),
    'date_from' => trim((string)($_GET['date_from'] ?? '')),
    'date_to' => trim((string)($_GET['date_to'] ?? ''))
];
$result = $securityAuditService->search($filters, max(1, (int)($_GET['page'] ?? 1)), 50);
$rows = $result['data'] ?? [];
$pageTitle = 'Audit Log Viewer';
require_once __DIR__ . '/../../../layouts/header.php'; require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content"><section class="card"><h2>Audit Log Viewer</h2><form method="GET"><input name="module" placeholder="Module" value="<?= e($filters['module']) ?>"><input name="action" placeholder="Action" value="<?= e($filters['action']) ?>"><input name="user_id" placeholder="User ID" value="<?= $filters['user_id'] ?: '' ?>"><input name="visit_id" placeholder="Encounter ID" value="<?= $filters['visit_id'] ?: '' ?>"><input name="department_id" placeholder="Department ID" value="<?= $filters['department_id'] ?: '' ?>"><input name="severity" placeholder="Severity" value="<?= e($filters['severity']) ?>"><input type="date" name="date_from" value="<?= e($filters['date_from']) ?>"><input type="date" name="date_to" value="<?= e($filters['date_to']) ?>"><button type="submit">Filter</button></form><table><thead><tr><th>Time</th><th>User</th><th>Module</th><th>Action</th><th>Department</th><th>Severity</th><th>Description</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e($row['created_at']) ?></td><td><?= e(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?></td><td><?= e($row['module']) ?></td><td><?= e($row['action']) ?></td><td><?= e((string)$row['department_name']) ?></td><td><?= e($row['severity']) ?></td><td><?= e($row['description']) ?></td></tr><?php endforeach; ?></tbody></table></section></main>
<?php if (($result['page'] ?? 1) > 1): ?><a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $result['page'] - 1]))) ?>">Previous</a><?php endif; ?>
<?php if ((($result['page'] ?? 1) * ($result['per_page'] ?? 50)) < ($result['total'] ?? 0)): ?> <a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $result['page'] + 1]))) ?>">Next</a><?php endif; ?>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
