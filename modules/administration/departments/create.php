<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$pageTitle = 'Create Department';
$formAction = 'save.php';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card"><h2>Create Department</h2><?php require __DIR__ . '/../partials/department_form.php'; ?></section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
