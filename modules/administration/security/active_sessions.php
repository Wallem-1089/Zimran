<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
if ($isSecurityAdministrator) {
    $sessions = $securitySessionService->getAllActiveSessions();
} else {
    $sessions = $securitySessionService->listActiveSessions((int)$currentUser['id']);
}
$pageTitle = 'Active Sessions';
require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';
?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card"><h2>Active Sessions</h2>
        <table><thead><tr><th>User</th><th>Login</th><th>Last Activity</th><th>IP</th><th>Browser</th><th>Department</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($sessions as $session): ?><tr>
            <td><?= e(($session['first_name'] ?? '') . ' ' . ($session['last_name'] ?? '')) ?></td><td><?= e($session['login_at']) ?></td><td><?= e($session['last_activity']) ?></td><td><?= e((string)$session['ip_address']) ?></td><td><?= e((string)$session['user_agent']) ?></td><td><?= e((string)$session['department_name']) ?></td>
            <td><form method="POST" action="terminate_session.php"><input type="hidden" name="session_id" value="<?= (int)$session['id'] ?>"><?= csrfField() ?><button type="submit">Terminate</button></form></td>
        </tr><?php endforeach; ?></tbody></table>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
