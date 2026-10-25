<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$cardId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = $pdo->prepare('SELECT * FROM family_cards WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $cardId]);
$card = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$card) { http_response_code(404); exit('Family Profile not found.'); }
$pageTitle = 'Edit Family Profile';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__ . '/../../layouts/navbar.php'; ?><main class="content">
    <div class="page-header"><div><h1>Edit Family Profile</h1><p><?= e((string)$card['card_number']) ?></p></div><a class="btn-secondary" href="view.php?id=<?= (int)$card['id'] ?>">Back</a></div>
    <?php if (!empty($_SESSION['validation_errors'])): ?><div class="alert-danger"><ul><?php foreach ((array)$_SESSION['validation_errors'] as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?></ul></div><?php unset($_SESSION['validation_errors']); endif; ?>
    <form method="post" action="update.php" class="card">
        <?= csrfField() ?><input type="hidden" name="family_card_id" value="<?= (int)$card['id'] ?>">
        <div class="form-grid">
            <div class="form-group"><label for="family_name">Family Name <span class="required">*</span></label><input id="family_name" name="family_name" value="<?= e((string)($card['family_name'] ?? $card['primary_contact_name'])) ?>" required></div>
            <div class="form-group"><label for="phone">Phone</label><input id="phone" name="phone" value="<?= e((string)($card['phone'] ?? '')) ?>"></div>
            <div class="form-group"><label for="member_capacity">Number of People</label><input id="member_capacity" name="member_capacity" type="number" min="1" max="100" value="<?= (int)($card['member_capacity'] ?? 4) ?>" <?= (string)$card['status'] === 'PendingPayment' ? '' : 'readonly' ?>><small class="form-help">Capacity can only be changed while payment is pending.</small></div>
            <div class="form-group" style="grid-column:1/-1"><label for="address">Address</label><textarea id="address" name="address" rows="3"><?= e((string)($card['address'] ?? '')) ?></textarea></div>
        </div>
        <div class="form-actions"><button class="btn-primary" type="submit">Save Family Profile</button></div>
    </form>
</main><?php require __DIR__ . '/../../layouts/footer.php'; ?></div>
