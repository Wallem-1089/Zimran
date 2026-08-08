<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
requireSecurityAdministrator();
$rows = $securityAuditService->search(['action' => 'ACCOUNT_LOCKED'], 1, 100)['data'] ?? [];
$users = $securityUserService->getUsers(['status' => 'Active']);
$pageTitle = 'Account Lockouts';
require_once __DIR__ . '/../../../layouts/header.php'; require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content"><section class="card"><h2>Account Lockouts</h2><table><thead><tr><th>Time</th><th>User</th><th>Description</th><th>Action</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e($row['created_at']) ?></td><td><?= e(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?></td><td><?= e($row['description']) ?></td><td><?php if (!empty($row['user_id'])): ?><form method="POST" action="unlock_account.php"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>"><button type="submit">Unlock</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></section></main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
