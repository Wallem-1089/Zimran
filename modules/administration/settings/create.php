<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Create Setting';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>

<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Create Custom Setting</h2>
        <form method="POST" action="save.php">
            <?= csrfField() ?>
            <label>Setting Key <input name="setting_key" required pattern="[a-z][a-z0-9_.-]{1,190}"></label>
            <label>Category <input name="setting_group" required></label>
            <label>Type
                <select name="setting_type"><option>string</option><option>integer</option><option>boolean</option><option>float</option><option>array</option></select>
            </label>
            <label>Value <textarea name="setting_value"></textarea></label>
            <label>Default Value <textarea name="default_value"></textarea></label>
            <label>Description <textarea name="description"></textarea></label>
            <label>Validation Rules (JSON) <textarea name="validation_rules">{}</textarea></label>
            <label>Sort Order <input type="number" name="sort_order" value="0"></label>
            <label><input type="checkbox" name="is_public" value="1"> Public</label>
            <label><input type="checkbox" name="is_sensitive" value="1"> Sensitive</label>
            <button type="submit">Create Setting</button>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
