<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Import Settings';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>

<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <h2>Settings Import</h2>
        <p>Planned: signed, validated settings packages will be imported here after encryption and deployment-policy support is implemented.</p>
        <p>No file is accepted by the current milestone.</p>
        <a href="index.php">Return to Settings</a>
    </section>
</main>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
