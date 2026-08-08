<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';
$departmentId = (int)($_GET['id'] ?? 0);
$department = $departmentService->getDepartment($departmentId);
if (!$department) { http_response_code(404); exit('Department not found.'); }
$pageTitle = 'Edit Department';
$formAction = 'update.php?id=' . $departmentId;
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card"><h2>Edit Department</h2><?php require __DIR__ . '/../partials/department_form.php'; ?></section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
