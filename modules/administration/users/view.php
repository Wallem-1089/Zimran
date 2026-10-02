<?php

declare(strict_types=1);

require_once __DIR__ . '/../partials/bootstrap.php';

$userId = (int)($_GET['id'] ?? 0);
$user = $userService->getUserById($userId);

if (!$user) {
    http_response_code(404);
    exit('User not found.');
}

administrationGuardSuperAdministratorUser($user, $currentUser, $permissionService);

$loadedStaffProfile = $staffProfileService->getProfile($userId);
$staffProfile = $loadedStaffProfile['profile'] ?? [];
$staffPhotoPath = (string)($staffProfile['profile_photo_path'] ?? '');
$staffPhotoUrl = $staffPhotoPath !== ''
    ? ('staff_photo.php?' . http_build_query([
        'id' => $userId,
        'v' => (string)($staffProfile['updated_at'] ?? time()),
    ]))
    : '';

$pageTitle = 'View User';
$success = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

require_once __DIR__ . '/../../../layouts/header.php';
require_once __DIR__ . '/../../../layouts/sidebar.php';

?>
<main class="content">
    <?php require_once __DIR__ . '/../../../layouts/navbar.php'; ?>
    <section class="card">
        <div class="page-header">
            <div>
                <h2><?= e($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                <p><?= e((string)$user['employee_id']) ?></p>
            </div>
            <div style="width:96px;height:96px;border:1px solid #d6e0ec;border-radius:12px;overflow:hidden;background:#f8fafc;display:flex;align-items:center;justify-content:center;">
                <?php if ($staffPhotoUrl !== ''): ?>
                    <img src="<?= e($staffPhotoUrl) ?>" alt="Staff profile picture" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <span class="text-muted">No Photo</span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($success): ?><p class="alert alert-success"><?= e($success) ?></p><?php endif; ?>
        <dl>
            <dt>Employee ID</dt><dd><?= e($user['employee_id']) ?></dd>
            <dt>Username</dt><dd><?= e($user['username']) ?></dd>
            <dt>Role</dt><dd><?= e($user['role_name']) ?></dd>
            <dt>Department</dt><dd><?= e($user['department_name']) ?></dd>
            <dt>Status</dt><dd><?= e($user['status']) ?></dd>
            <dt>Account Lock</dt><dd><?= !empty($user['locked_at']) ? 'Locked' : 'Unlocked' ?></dd>
            <dt>Must Change Password</dt><dd><?= !empty($user['must_change_password']) ? 'Yes' : 'No' ?></dd>
            <dt>Last Login</dt><dd><?= e((string)($user['last_login'] ?? 'Never')) ?></dd>
        </dl>
        <p>
            <a class="btn-primary" href="edit.php?id=<?= $userId ?>">Edit</a>
            <a href="staff_profile.php?id=<?= $userId ?>">Staff Profile</a>
            <a href="reset_password.php?id=<?= $userId ?>">Reset Password</a>
            <a href="departments.php?id=<?= $userId ?>">Departments</a>
        </p>
        <form method="POST" action="action.php" style="display:inline">
            <?= csrfField() ?>
            <input type="hidden" name="user_id" value="<?= $userId ?>">
            <?php if ($user['status'] === 'Active'): ?>
                <button name="action" value="deactivate">Deactivate</button>
            <?php else: ?>
                <button name="action" value="activate">Activate</button>
            <?php endif; ?>
            <?php if (!empty($user['locked_at'])): ?>
                <button name="action" value="unlock">Unlock</button>
            <?php else: ?>
                <button name="action" value="lock">Lock</button>
            <?php endif; ?>
            <button name="action" value="force_password_change">Force Password Change</button>
        </form>
    </section>
</main>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
