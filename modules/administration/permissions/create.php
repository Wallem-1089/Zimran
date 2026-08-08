<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$pageTitle = 'Create Permission';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Create Permission</h2>
        <form method="POST" action="save.php">
            <?= csrfField() ?>
            <label>Permission Key <input name="permission_key" required></label>
            <label>Permission Name <input name="permission_name" required></label>
            <label>Module <input name="module" required></label>
            <label>Description <textarea name="description"></textarea></label>
            <button class="btn-primary" type="submit">Save Permission</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
