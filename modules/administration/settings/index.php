<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$search = trim((string)($_GET['search'] ?? ''));
$selectedGroup = trim((string)($_GET['group'] ?? ''));
$groups = $settingsService->listGroups();
$settings = $settingsService->search(
    $search,
    $selectedGroup === '' ? null : $selectedGroup
);
$pageTitle = 'System Settings';

require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>

<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>

    <section class="card">
        <h2>Enterprise System Settings</h2>
        <p>Central configuration for hospital, security, encounter, queue, notification, reporting, backup, and system behavior.</p>
        <p>
            <a class="btn-primary" href="create.php">Create Setting</a>
            <a class="btn-primary" href="history.php">Setting History</a>
            <a class="btn-primary" href="export.php">Export Settings</a>
            <a class="btn-primary" href="import.php">Import Settings</a>
        </p>

        <form method="GET">
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Search key or description">
            <select name="group">
                <option value="">All categories</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?= e($group['setting_group']) ?>" <?= $selectedGroup === $group['setting_group'] ? 'selected' : '' ?>>
                        <?= e($group['setting_group']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Search</button>
        </form>
    </section>

    <section class="stats">
        <?php foreach ($groups as $group): ?>
            <article class="card">
                <h3><?= e($group['setting_group']) ?></h3>
                <p><?= (int)$group['setting_count'] ?> settings</p>
                <a href="category.php?group=<?= urlencode((string)$group['setting_group']) ?>">Manage Category</a>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <h3><?= $search !== '' || $selectedGroup !== '' ? 'Search Results' : 'All Settings' ?></h3>
        <?php if ($settings === []): ?>
            <p>No settings matched the current filters.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Key</th><th>Category</th><th>Type</th><th>Value</th><th>Access</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($settings as $setting): ?>
                    <tr>
                        <td><?= e($setting['setting_key']) ?></td>
                        <td><?= e($setting['setting_group']) ?></td>
                        <td><?= e($setting['setting_type']) ?></td>
                        <td><?= !empty($setting['is_sensitive']) ? '[REDACTED]' : e(is_array($setting['typed_value']) ? json_encode($setting['typed_value']) : (string)$setting['typed_value']) ?></td>
                        <td><?= !empty($setting['is_public']) ? 'Public' : 'Internal' ?></td>
                        <td><a href="edit.php?key=<?= urlencode((string)$setting['setting_key']) ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
