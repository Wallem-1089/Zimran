<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!familyCardTablesReady($pdo)) {
    http_response_code(503);
    exit('Family Card tables are not available. Apply migration 095.');
}

$filters = [
    'search' => trim((string)($_GET['search'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
];

$where = ['1 = 1'];
$params = [];
if ($filters['search'] !== '') {
    $where[] = '(fc.card_number LIKE :search OR fc.primary_contact_name LIKE :search OR fc.phone LIKE :search)';
    $params[':search'] = '%' . $filters['search'] . '%';
}
if ($filters['status'] !== '' && in_array($filters['status'], ['PendingPayment', 'Active', 'Suspended', 'Expired', 'Cancelled'], true)) {
    $where[] = 'fc.status = :status';
    $params[':status'] = $filters['status'];
}

$stmt = $pdo->prepare('
    SELECT fc.*,
           COUNT(CASE WHEN fcm.status = \'Active\' THEN 1 END) AS active_members
    FROM family_cards fc
    LEFT JOIN family_card_members fcm ON fcm.family_card_id = fc.id
    WHERE ' . implode(' AND ', $where) . '
    GROUP BY fc.id
    ORDER BY fc.created_at DESC, fc.id DESC
    LIMIT 200
');
$stmt->execute($params);
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$pageTitle = 'Family Cards';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= e((string)$_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert-danger"><?= e((string)$_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div>
            <h1>Family Cards</h1>
            <p>Manage family plan cards and members.</p>
        </div>
    </div>

    <form method="get" class="card">
        <div class="form-grid">
            <div class="form-group">
                <label for="search">Search</label>
                <input id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Card number, contact, phone">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All</option>
                    <?php foreach (['PendingPayment', 'Active', 'Suspended', 'Expired', 'Cancelled'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Filter</button>
            <a class="btn-secondary" href="index.php">Reset</a>
        </div>
    </form>

    <div class="card">
        <?php if ($cards === []): ?>
            <div class="empty-state">No Family Cards found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Card Number</th>
                            <th>Primary Contact</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Valid Until</th>
                            <th>Members</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cards as $card): ?>
                            <tr>
                                <td><?= e((string)$card['card_number']) ?></td>
                                <td><?= e((string)$card['primary_contact_name']) ?></td>
                                <td><?= e((string)($card['phone'] ?? '-')) ?></td>
                                <td><?= e((string)$card['status']) ?></td>
                                <td><?= e((string)($card['valid_until'] ?? '-')) ?></td>
                                <td><?= (int)$card['active_members'] ?></td>
                                <td><a class="btn-secondary btn-sm" href="view.php?id=<?= (int)$card['id'] ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
