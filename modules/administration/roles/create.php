<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$pageTitle = 'Create Role';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Create Role</h2>
        <form method="POST" action="save.php">
            <?= csrfField() ?>
            <label>Role Name <input name="role_name" required></label>
            <label>Description <textarea name="description"></textarea></label>
            <button class="btn-primary" type="submit">Save Role</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
