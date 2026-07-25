<?php

declare(strict_types=1);

$pageTitle = 'Layout Test';

$currentUser = [
    'first_name' => 'Walter',
    'last_name' => 'Ikhile',
    'role_name' => 'System Administrator',
    'department_name' => 'Administrator'
];

require_once '../config/helpers.php';
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';
require_once '../layouts/navbar.php';
?>

<main class="content">

    <h2>Layout Test</h2>

    <p>If you can see this page with the sidebar, navbar, and footer, your layout is working correctly.</p>

</main>

<?php require_once '../layouts/footer.php'; ?>