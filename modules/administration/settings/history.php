<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$key = trim((string)($_GET['key'] ?? ''));
$group = trim((string)($_GET['group'] ?? ''));
$result = $settingsService->getHistory(
    $key === '' ? null : $key,
    $group === '' ? null : $group,
    max(1, (int)($_GET['page'] ?? 1)),
    50
);
$rows = $result['data'];
$pageTitle = 'Setting History';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>

<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Setting History</h2>
        <form method="GET"><input name="key" value="<?= e($key) ?>" placeholder="Setting key"><input name="group" value="<?= e($group) ?>" placeholder="Category"><button type="submit">Filter</button></form>
        <table><thead><tr><th>Time</th><th>Setting</th><th>Category</th><th>Action</th><th>Old</th><th>New</th><th>User</th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?>
            <tr><td><?= e($row['created_at']) ?></td><td><?= e($row['setting_key']) ?></td><td><?= e($row['setting_group']) ?></td><td><?= e($row['action']) ?></td><td><?= e((string)$row['old_value']) ?></td><td><?= e((string)$row['new_value']) ?></td><td><?= e(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
        <?php if ($result['page'] > 1): ?><a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $result['page'] - 1]))) ?>">Previous</a><?php endif; ?>
        <?php if ($result['page'] * $result['per_page'] < $result['total']): ?><a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $result['page'] + 1]))) ?>">Next</a><?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
