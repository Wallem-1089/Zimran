<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$userId = $isSecurityAdministrator ? (int)($_GET['user_id'] ?? 0) : (int)$currentUser['id'];
$history = $securityAuditService->userHistory($userId, 'Authentication')['data'] ?? [];
$pageTitle = 'Login History';
require_once __DIR__ . '/../../../layouts/header.php'; require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content"><section class="card"><h2>Login History</h2><table><thead><tr><th>Time</th><th>Action</th><th>IP</th><th>User Agent</th><th>Severity</th></tr></thead><tbody><?php foreach ($history as $row): ?><tr><td><?= e($row['created_at']) ?></td><td><?= e($row['action']) ?></td><td><?= e((string)$row['ip_address']) ?></td><td><?= e((string)$row['user_agent']) ?></td><td><?= e($row['severity']) ?></td></tr><?php endforeach; ?></tbody></table></section></main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
