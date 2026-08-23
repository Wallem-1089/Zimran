<?php

declare(strict_types=1);

$pageTitle = 'My Notifications';
$moduleStylesheet = '/modules/visits/assets/visits.css';

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/UserNotificationService.php';

$status = (string)($_GET['status'] ?? '');
$service = new UserNotificationService($pdo);
$notifications = $service->listForUser((int)$currentUser['id'], $status);

$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);
$successMessage = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';
?>

<div class="main-container">
<?php require_once __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>My Notifications</h1>
            <p>Direct attention requests sent to your user account.</p>
        </div>
    </div>

    <?php if ($errorMessage): ?><div class="alert-danger"><?= nl2br(e((string)$errorMessage)) ?></div><?php endif; ?>
    <?php if ($successMessage): ?><div class="alert-success"><?= nl2br(e((string)$successMessage)) ?></div><?php endif; ?>

    <div class="card">
        <form method="get" class="form-grid">
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    <?php foreach (['Unread', 'Read', 'Resolved'] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button class="btn-secondary" type="submit">Filter</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Inbox</h2>
        <?php if ($notifications === []): ?>
            <p>No user notifications found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Hospital No.</th>
                            <th>Visit</th>
                            <th>Message</th>
                            <th>Sender</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notification): ?>
                            <tr>
                                <td><?= e((string)$notification['first_name']) ?> <?= e((string)$notification['last_name']) ?></td>
                                <td><?= e((string)$notification['hospital_number']) ?></td>
                                <td><?= e((string)$notification['visit_number']) ?></td>
                                <td><?= nl2br(e((string)$notification['message'])) ?></td>
                                <td><?= e((string)$notification['sender_name']) ?></td>
                                <td><?= e((string)$notification['created_at']) ?></td>
                                <td><?= e((string)$notification['status']) ?></td>
                                <td>
                                    <a class="btn-secondary" href="../visits/workspace.php?id=<?= (int)$notification['visit_id'] ?>">Open Encounter</a>
                                    <?php if ((string)$notification['status'] === 'Unread'): ?>
                                        <form method="post" action="mark_read.php" style="display:inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int)$notification['id'] ?>">
                                            <button class="btn-secondary" type="submit">Mark Read</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ((string)$notification['status'] !== 'Resolved'): ?>
                                        <form method="post" action="resolve.php" style="display:inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int)$notification['id'] ?>">
                                            <button class="btn-primary" type="submit">Resolve</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
</div>

