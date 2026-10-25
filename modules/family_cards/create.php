<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$pageTitle = 'Create Family Profile';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header"><div><h1>Create Family Profile</h1><p>Payment is required before patients can be added.</p></div><a class="btn-secondary" href="index.php">Back</a></div>
    <?php if (!empty($_SESSION['error_message'])): ?><div class="alert-danger"><?= e((string)$_SESSION['error_message']); unset($_SESSION['error_message']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['validation_errors'])): ?><div class="alert-danger"><ul><?php foreach ((array)$_SESSION['validation_errors'] as $error): ?><li><?= e((string)$error) ?></li><?php endforeach; ?></ul><?php unset($_SESSION['validation_errors']); ?></div><?php endif; ?>
    <form method="post" action="save.php" class="card">
        <?= csrfField() ?>
        <div class="form-grid">
            <div class="form-group"><label for="family_name">Family Name <span class="required">*</span></label><input id="family_name" name="family_name" required></div>
            <div class="form-group"><label for="phone">Phone</label><input id="phone" name="phone"></div>
            <div class="form-group"><label for="member_capacity">Number of People <span class="required">*</span></label><input id="member_capacity" name="member_capacity" type="number" min="1" max="100" value="4" required></div>
            <div class="form-group"><label>Activation Fee</label><div class="readonly-field" id="activation_fee">₦50,000.00</div><small class="form-help">Up to 4 people: ₦50,000. Each additional person: ₦10,000.</small></div>
            <div class="form-group" style="grid-column:1/-1"><label for="address">Address</label><textarea id="address" name="address" rows="3"></textarea></div>
        </div>
        <div class="form-actions"><button class="btn-primary" type="submit">Create Family and Send to Accounts</button></div>
    </form>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
<script>
(function () { const input = document.getElementById('member_capacity'); const output = document.getElementById('activation_fee'); if (!input || !output) return; const update = () => { const count = Math.max(1, parseInt(input.value || '1', 10)); const fee = 50000 + Math.max(0, count - 4) * 10000; output.textContent = '₦' + fee.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }; input.addEventListener('input', update); update(); }());
</script>
