<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$key = trim((string)($_GET['key'] ?? ''));
$setting = $settingsService->getSettingDefinition($key);

if (!$setting) {
    http_response_code(404);
    exit('Setting not found.');
}

$displayValue = is_array($setting['typed_value'])
    ? json_encode($setting['typed_value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    : (string)$setting['typed_value'];
$pageTitle = 'Edit Setting';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>

<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2><?= e($setting['setting_key']) ?></h2>
        <p><?= e((string)$setting['description']) ?></p>
        <p>Category: <?= e($setting['setting_group']) ?> | Type: <?= e($setting['setting_type']) ?></p>
        <form method="POST" action="update.php">
            <?= csrfField() ?>
            <input type="hidden" name="setting_key" value="<?= e($setting['setting_key']) ?>">
            <?php if ($setting['setting_type'] === 'boolean'): ?>
                <select name="setting_value" <?= empty($setting['is_editable']) ? 'disabled' : '' ?>><option value="1" <?= !empty($setting['typed_value']) ? 'selected' : '' ?>>Enabled</option><option value="0" <?= empty($setting['typed_value']) ? 'selected' : '' ?>>Disabled</option></select>
            <?php else: ?>
                <textarea name="setting_value" <?= empty($setting['is_editable']) ? 'disabled' : '' ?>><?= !empty($setting['is_sensitive']) ? '' : e($displayValue) ?></textarea>
            <?php endif; ?>
            <?php if (!empty($setting['is_editable'])): ?><button type="submit">Update Setting</button><?php endif; ?>
        </form>
        <?php if (!empty($setting['is_editable'])): ?>
            <form method="POST" action="reset.php"><?= csrfField() ?><input type="hidden" name="setting_key" value="<?= e($setting['setting_key']) ?>"><button type="submit">Reset to Default</button></form>
        <?php endif; ?>
        <?php if (empty($setting['is_system']) && !empty($setting['is_editable'])): ?>
            <form method="POST" action="delete.php"><?= csrfField() ?><input type="hidden" name="setting_key" value="<?= e($setting['setting_key']) ?>"><button type="submit">Delete Custom Setting</button></form>
        <?php endif; ?>
        <p><a href="history.php?key=<?= urlencode($key) ?>">View Setting History</a></p>
    </section>
</main>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
