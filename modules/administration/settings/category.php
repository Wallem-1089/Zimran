<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$group = trim((string)($_GET['group'] ?? ''));
$settings = $group === '' ? [] : $settingsService->getGroup($group);

if ($group === '' || $settings === []) {
    http_response_code(404);
    exit('Settings category not found.');
}

$pageTitle = $group . ' Settings';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>

<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2><?= e($group) ?> Settings</h2>
        <p><a href="index.php">All Settings</a> | <a href="history.php?group=<?= urlencode($group) ?>">Category History</a></p>
        <form method="POST" action="bulk_update.php">
            <?= csrfField() ?>
            <input type="hidden" name="group" value="<?= e($group) ?>">
            <?php foreach ($settings as $setting): ?>
                <div class="form-group">
                    <label for="setting_<?= (int)$setting['id'] ?>">
                        <?= e($setting['setting_key']) ?>
                    </label>
                    <?php if ($setting['setting_type'] === 'boolean'): ?>
                        <select id="setting_<?= (int)$setting['id'] ?>" name="settings[<?= e($setting['setting_key']) ?>]" <?= empty($setting['is_editable']) ? 'disabled' : '' ?>>
                            <option value="1" <?= !empty($setting['typed_value']) ? 'selected' : '' ?>>Enabled</option>
                            <option value="0" <?= empty($setting['typed_value']) ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    <?php elseif ($setting['setting_type'] === 'array'): ?>
                        <textarea id="setting_<?= (int)$setting['id'] ?>" name="settings[<?= e($setting['setting_key']) ?>]" <?= empty($setting['is_editable']) ? 'disabled' : '' ?>><?= e(json_encode($setting['typed_value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
                    <?php else: ?>
                        <input id="setting_<?= (int)$setting['id'] ?>" name="settings[<?= e($setting['setting_key']) ?>]" value="<?= !empty($setting['is_sensitive']) ? '' : e((string)$setting['typed_value']) ?>" <?= empty($setting['is_editable']) ? 'disabled' : '' ?>>
                    <?php endif; ?>
                    <small><?= e((string)$setting['description']) ?> — <?= e($setting['setting_type']) ?><?= empty($setting['is_editable']) ? ' — read only' : '' ?></small>
                    <a href="edit.php?key=<?= urlencode((string)$setting['setting_key']) ?>">Open setting</a>
                </div>
            <?php endforeach; ?>
            <button type="submit">Save Category</button>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
