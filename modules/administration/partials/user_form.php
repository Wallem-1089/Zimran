<?php

declare(strict_types=1);

$user = $user ?? [];
$roles = $roles ?? [];
$departments = $departments ?? [];
$isEdit = $isEdit ?? false;
$currentUser = $currentUser ?? ($_SESSION['user'] ?? []);
$canAssignSuperAdministrator = ($currentUser['role_name'] ?? '') === 'Super Administrator'
    || ($currentUser['department_name'] ?? '') === 'Super Administrator';

?>

<form method="POST" action="<?= e($formAction) ?>">

    <?= csrfField() ?>

    <div class="form-grid">

        <label>Employee ID
            <input name="employee_id" required value="<?= e($user['employee_id'] ?? '') ?>">
        </label>

        <label>Username
            <input name="username" required value="<?= e($user['username'] ?? '') ?>">
        </label>

        <label>First Name
            <input name="first_name" required value="<?= e($user['first_name'] ?? '') ?>">
        </label>

        <label>Last Name
            <input name="last_name" required value="<?= e($user['last_name'] ?? '') ?>">
        </label>

        <label>Gender
            <select name="gender">
                <option value="">Select</option>
                <?php foreach (['Male', 'Female'] as $gender): ?>
                    <option value="<?= e($gender) ?>" <?= ($user['gender'] ?? '') === $gender ? 'selected' : '' ?>>
                        <?= e($gender) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Phone
            <input name="phone" value="<?= e($user['phone'] ?? '') ?>">
        </label>

        <label>Email
            <input type="email" name="email" value="<?= e($user['email'] ?? '') ?>">
        </label>

        <label>Department
            <select name="department_id" required>
                <option value="">Select department</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= (int)$department['id'] ?>" <?= (int)($user['department_id'] ?? 0) === (int)$department['id'] ? 'selected' : '' ?>>
                        <?= e($department['department_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Role
            <select name="role_id" required>
                <option value="">Select role</option>
                <?php foreach ($roles as $role): ?>
                    <?php if (($role['role_name'] ?? '') === 'Super Administrator' && !$canAssignSuperAdministrator): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <option value="<?= (int)$role['id'] ?>" <?= (int)($user['role_id'] ?? 0) === (int)$role['id'] ? 'selected' : '' ?>>
                        <?= e($role['role_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if (!$isEdit): ?>
            <label>Password
                <input type="password" name="password" minlength="8" required>
            </label>
        <?php endif; ?>

        <label>Status
            <select name="status">
                <?php foreach (['Active', 'Inactive'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($user['status'] ?? 'Active') === $status ? 'selected' : '' ?>>
                        <?= e($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <input type="checkbox" name="must_change_password" value="1" <?= !empty($user['must_change_password']) ? 'checked' : '' ?>>
            Force password change
        </label>

    </div>

    <button type="submit" class="btn-primary">Save User</button>

</form>
