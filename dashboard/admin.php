<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/DashboardService.php';

$permissionService = new PermissionService($pdo);

if (!$permissionService->isAdministrator($currentUser)) {
    securityFailure(
        'Unauthorized administrator dashboard access attempt.',
        null,
        'ADMIN_DASHBOARD_ACCESS_DENIED'
    );
}

$dashboardService = new DashboardService($pdo);
$dashboardService->recordDashboardView((int)($currentUser['id'] ?? 0));
$dashboard = $dashboardService->getAdministratorDashboard();
$data = $dashboard['data'] ?? [];
$users = $data['users'] ?? [];
$departments = $data['departments'] ?? [];
$encounters = $data['encounters'] ?? [];
$queue = $data['queue'] ?? [];
$security = $data['security'] ?? [];
$audit = $data['audit'] ?? [];
$charts = $data['charts'] ?? [];
$pageTitle = 'Administrator Dashboard';
$moduleStylesheet = '/assets/css/admin-dashboard.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

function dashboardCount(array $values, string $key): int
{
    return (int)($values[$key] ?? 0);
}

?>

<main class="content admin-dashboard">
    <?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

    <section class="dashboard-heading">
        <div>
            <h2>Operational Overview</h2>
            <p>Live hospital activity and administration metrics.</p>
        </div>
        <nav class="quick-actions" aria-label="Administration shortcuts">
            <a href="<?= e($baseUrl) ?>/modules/administration/users/index.php">Users</a>
            <a href="<?= e($baseUrl) ?>/modules/administration/roles/index.php">Roles</a>
            <a href="<?= e($baseUrl) ?>/modules/administration/permissions/index.php">Permissions</a>
            <a href="<?= e($baseUrl) ?>/modules/administration/departments/index.php">Departments</a>
            <a href="<?= e($baseUrl) ?>/modules/administration/security/dashboard.php">Security</a>
            <a href="<?= e($baseUrl) ?>/modules/administration/security/audit_logs.php">Audit Logs</a>
            <a href="<?= e($baseUrl) ?>/modules/administration/settings/index.php">Settings</a>
        </nav>
    </section>

    <?php if (!$dashboard['success']): ?>
        <div class="alert alert-danger">Unable to load dashboard statistics.</div>
    <?php endif; ?>

    <section class="stat-grid" aria-label="Summary statistics">
        <?php foreach ([
            ['Users', dashboardCount($users, 'total'), 'Active ' . dashboardCount($users, 'active')],
            ['Departments', dashboardCount($departments, 'total'), 'Active ' . dashboardCount($departments, 'active')],
            ['Encounters', dashboardCount($encounters, 'total'), 'Active ' . dashboardCount($encounters, 'active')],
            ['Waiting Queue', dashboardCount($queue, 'waiting'), 'In service ' . dashboardCount($queue, 'in_service')],
            ['Active Sessions', dashboardCount($security, 'active_sessions'), 'Locked ' . dashboardCount($security, 'locked_accounts')],
            ['Failed Logins Today', dashboardCount($security, 'failed_logins_today'), 'Successful ' . dashboardCount($security, 'successful_logins_today')]
        ] as $card): ?>
            <article class="stat-card">
                <span><?= e($card[0]) ?></span>
                <strong><?= (int)$card[1] ?></strong>
                <small><?= e($card[2]) ?></small>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="dashboard-columns">
        <article class="card">
            <h3>Encounter Status</h3>
            <div class="metric-list">
                <?php foreach ([
                    ['Waiting', dashboardCount($encounters, 'waiting')],
                    ['Received', dashboardCount($encounters, 'received')],
                    ['In Consultation', dashboardCount($encounters, 'in_consultation')],
                    ['Laboratory', dashboardCount($encounters, 'laboratory')],
                    ['Pharmacy', dashboardCount($encounters, 'pharmacy')],
                    ['Completed', dashboardCount($encounters, 'completed')],
                    ['Cancelled', dashboardCount($encounters, 'cancelled')]
                ] as $metric): ?>
                    <div><span><?= e($metric[0]) ?></span><strong><?= (int)$metric[1] ?></strong></div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card">
            <h3>Queue Summary</h3>
            <div class="metric-list">
                <div><span>Waiting</span><strong><?= dashboardCount($queue, 'waiting') ?></strong></div>
                <div><span>Called</span><strong><?= dashboardCount($queue, 'called') ?></strong></div>
                <div><span>In service</span><strong><?= dashboardCount($queue, 'in_service') ?></strong></div>
                <div><span>Average queue length</span><strong><?= e((string)($queue['average_length'] ?? 0)) ?></strong></div>
            </div>
            <h4>By Department</h4>
            <?php if (empty($queue['by_department'])): ?><p>No active queues.</p><?php endif; ?>
            <?php foreach (($queue['by_department'] ?? []) as $row): ?>
                <div class="bar-row"><span><?= e($row['department_name']) ?></span><strong><?= (int)$row['total'] ?></strong></div>
            <?php endforeach; ?>
        </article>

        <article class="card">
            <h3>User and Role Summary</h3>
            <div class="metric-list">
                <div><span>Active</span><strong><?= dashboardCount($users, 'active') ?></strong></div>
                <div><span>Inactive</span><strong><?= dashboardCount($users, 'inactive') ?></strong></div>
                <div><span>Locked</span><strong><?= dashboardCount($users, 'locked') ?></strong></div>
                <?php foreach (($users['by_role'] ?? []) as $role => $count): ?>
                    <div><span><?= e($role) ?></span><strong><?= (int)$count ?></strong></div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="dashboard-columns">
        <article class="card">
            <h3>Departments</h3>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Department</th><th>Users</th><th>Encounters</th><th>Queue</th></tr></thead>
                    <tbody>
                    <?php foreach (($departments['items'] ?? []) as $department): ?>
                        <tr>
                            <td><?= e($department['department_name']) ?></td>
                            <td><?= (int)$department['active_users'] ?></td>
                            <td><?= (int)$department['active_encounters'] ?></td>
                            <td><?= !empty($department['queue_enabled']) ? 'Enabled' : 'Disabled' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card">
            <h3>Recent Audit Activity</h3>
            <?php if (empty($audit['recent'])): ?><p>No audit activity recorded.</p><?php endif; ?>
            <?php foreach (array_slice($audit['recent'] ?? [], 0, 8) as $event): ?>
                <div class="activity-row"><strong><?= e($event['action'] ?? '') ?></strong><small><?= e($event['created_at'] ?? '') ?></small></div>
            <?php endforeach; ?>
        </article>
    </section>

    <section class="dashboard-columns charts" aria-label="Dashboard charts">
        <?php foreach ([
            ['Encounter status distribution', $encounters['by_status'] ?? []],
            ['Login activity, last 7 days', $charts['login_activity'] ?? []],
            ['Audit activity, last 7 days', $charts['audit_activity'] ?? []]
        ] as $chart): ?>
            <article class="card chart-card">
                <h3><?= e($chart[0]) ?></h3>
                <?php if (empty($chart[1])): ?><p>No data available.</p><?php endif; ?>
                <?php foreach ($chart[1] as $label => $value):
                    if (is_array($value)) {
                        $label = $value['activity_date'] ?? '';
                        $value = $value['total'] ?? 0;
                    }
                    $value = (int)$value;
                ?>
                    <div class="bar-row"><span><?= e((string)$label) ?></span><strong><?= $value ?></strong></div>
                <?php endforeach; ?>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
