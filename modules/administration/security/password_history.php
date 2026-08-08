<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
$userId = $isSecurityAdministrator ? (int)($_GET['user_id'] ?? 0) : (int)$currentUser['id'];
$rows = $securityUserService->getPasswordHistory($userId);
$pageTitle = 'Password History';
require_once __DIR__ . '/../../../layouts/header.php'; require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content"><section class="card"><h2>Password History</h2><table><thead><tr><th>Changed</th><th>Type</th><th>Changed By</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e($row['created_at']) ?></td><td><?= e($row['change_type']) ?></td><td><?= e((string)$row['changed_by_username']) ?></td></tr><?php endforeach; ?></tbody></table></section></main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
