<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$cardId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($cardId <= 0 || !familyCardTablesReady($pdo)) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM family_cards WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $cardId]);
$card = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$card) {
    http_response_code(404);
    exit('Family Card not found.');
}

$membersStmt = $pdo->prepare('
    SELECT fcm.*,
           p.hospital_number,
           p.first_name,
           p.middle_name,
           p.last_name,
           p.phone
    FROM family_card_members fcm
    INNER JOIN patients p ON p.id = fcm.patient_id
    WHERE fcm.family_card_id = :family_card_id
    ORDER BY fcm.status = \'Active\' DESC, fcm.member_type ASC, p.last_name ASC
');
$membersStmt->execute([':family_card_id' => $cardId]);
$members = $membersStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$pageTitle = 'Family Card';
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
            <h1>Family Card <?= e((string)$card['card_number']) ?></h1>
            <p><?= e((string)$card['primary_contact_name']) ?></p>
        </div>
        <div><a class="btn-secondary" href="index.php">Back</a></div>
    </div>

    <div class="card">
        <div class="summary-grid">
            <div class="summary-item"><span class="summary-label">Status</span> <span class="summary-value"><?= e((string)$card['status']) ?></span></div>
            <div class="summary-item"><span class="summary-label">Valid Until</span> <span class="summary-value"><?= e((string)($card['valid_until'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Phone</span> <span class="summary-value"><?= e((string)($card['phone'] ?? '-')) ?></span></div>
            <div class="summary-item"><span class="summary-label">Activation Fee</span> <span class="summary-value">&#8358;<?= e(number_format((float)$card['activation_fee'], 2)) ?></span></div>
            <div class="summary-item"><span class="summary-label">Renewal Fee</span> <span class="summary-value">&#8358;<?= e(number_format((float)$card['renewal_fee'], 2)) ?></span></div>
        </div>
    </div>

    <?php if ((string)$card['status'] === 'Active'): ?>
        <form method="post" action="add_member.php" class="card">
            <?= csrfField() ?>
            <input type="hidden" name="family_card_id" value="<?= (int)$card['id'] ?>">
            <h3>Add Existing Patient to Family Card</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="hospital_number">Hospital Number <span class="required">*</span></label>
                    <input id="hospital_number" name="hospital_number" required>
                </div>
                <div class="form-group">
                    <label for="member_type">Member Type</label>
                    <select id="member_type" name="member_type">
                        <?php foreach (['Dependent', 'Spouse', 'Child', 'Other'] as $type): ?>
                            <option value="<?= e($type) ?>"><?= e($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="relationship">Relationship</label>
                    <input id="relationship" name="relationship" maxlength="100">
                </div>
            </div>
            <div class="form-actions">
                <button class="btn-primary" type="submit">Add Member</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="card">
        <h3>Members</h3>
        <?php if ($members === []): ?>
            <div class="empty-state">No members added yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Hospital No.</th>
                            <th>Phone</th>
                            <th>Member Type</th>
                            <th>Relationship</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?= e(trim((string)$member['first_name'] . ' ' . (string)($member['middle_name'] ?? '') . ' ' . (string)$member['last_name'])) ?></td>
                                <td><?= e((string)($member['hospital_number'] ?? '-')) ?></td>
                                <td><?= e((string)($member['phone'] ?? '-')) ?></td>
                                <td><?= e((string)$member['member_type']) ?></td>
                                <td><?= e((string)($member['relationship'] ?? '-')) ?></td>
                                <td><?= e((string)$member['status']) ?></td>
                                <td>
                                    <a class="btn-secondary btn-sm" href="../patients/view.php?id=<?= (int)$member['patient_id'] ?>">Patient</a>
                                    <?php if ((string)$member['status'] === 'Active' && (string)$member['member_type'] !== 'Principal'): ?>
                                        <form method="post" action="remove_member.php" class="inline-form">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="family_card_id" value="<?= (int)$card['id'] ?>">
                                            <input type="hidden" name="member_id" value="<?= (int)$member['id'] ?>">
                                            <button class="btn-secondary btn-sm" type="submit" onclick="return confirm('Remove this member from the Family Card?');">Remove</button>
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
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
