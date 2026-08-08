<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireSecurityAdministrator();
$result = $securityAuditService->search(['action' => 'LOGIN_FAILED'], max(1, (int)($_GET['page'] ?? 1)), 50);
$rows = $result['data'] ?? [];
$pageTitle = 'Failed Logins';
require_once __DIR__ . '/../../../layouts/header.php'; require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content"><section class="card"><h2>Failed Logins</h2><table><thead><tr><th>Time</th><th>IP</th><th>User Agent</th><th>Description</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e($row['created_at']) ?></td><td><?= e((string)$row['ip_address']) ?></td><td><?= e((string)$row['user_agent']) ?></td><td><?= e($row['description']) ?></td></tr><?php endforeach; ?></tbody></table></section></main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
