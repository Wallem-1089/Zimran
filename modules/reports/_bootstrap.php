<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/DashboardService.php';
require_once __DIR__ . '/../../services/PermissionService.php';

$permissionService = new PermissionService($pdo);
$dashboardService = new DashboardService($pdo);

function reportsRequireAccess(PermissionService $permissionService, ?array $user): void
{
    if (!$permissionService->canViewReports($user)) {
        http_response_code(403);
        exit('You are not allowed to view reports.');
    }
}

function reportsDateFilters(): array
{
    return [
        'date_from' => trim((string)($_GET['date_from'] ?? date('Y-m-d'))),
        'date_to' => trim((string)($_GET['date_to'] ?? date('Y-m-d'))),
        'department_id' => (int)($_GET['department_id'] ?? 0),
        'status' => trim((string)($_GET['status'] ?? '')),
        'item_id' => (int)($_GET['item_id'] ?? 0),
    ];
}

function reportsFilterForm(array $filters, array $departments, array $options = []): void
{
    $showStatus = !empty($options['status']);
    $showItems = !empty($options['items']);
    $items = $options['items'] ?? [];
    ?>
    <form method="get" class="card">
        <div class="form-grid">
            <div class="form-group">
                <label for="date_from">Date From</label>
                <input id="date_from" name="date_from" type="date" value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="form-group">
                <label for="date_to">Date To</label>
                <input id="date_to" name="date_to" type="date" value="<?= e($filters['date_to']) ?>">
            </div>
            <div class="form-group">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id">
                    <option value="0">All</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= (int)$department['id'] ?>" <?= (int)$filters['department_id'] === (int)$department['id'] ? 'selected' : '' ?>>
                            <?= e((string)$department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($showStatus): ?>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <?php foreach (['Waiting','Reception','Records','Nursing','Doctor','Laboratory','X-Ray','Pharmacy','Physiotherapy','Theatre','Accounts','Store','Completed','Cancelled'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if ($showItems): ?>
                <div class="form-group">
                    <label for="item_id">Item</label>
                    <select id="item_id" name="item_id">
                        <option value="0">All</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int)$item['id'] ?>" <?= (int)$filters['item_id'] === (int)$item['id'] ? 'selected' : '' ?>>
                                <?= e((string)$item['item_code']) ?> - <?= e((string)$item['item_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Apply Filters</button>
            <a class="btn-secondary" href="<?= e(basename($_SERVER['PHP_SELF'])) ?>">Reset</a>
        </div>
    </form>
    <?php
}
