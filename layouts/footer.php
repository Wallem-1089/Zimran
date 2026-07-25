<?php

declare(strict_types=1);

$currentYear = date('Y');
$config = require __DIR__ . '/../config/app.php';

$baseUrl = rtrim($config['app']['base_url'], '/');

?>

<footer class="footer">

    <div class="footer-left">

        <strong>Hospital Management System</strong>

        <span>

            © <?= $currentYear ?>

            All Rights Reserved.

        </span>

    </div>

    <div class="footer-right">

        Version 1.0.0

    </div>

</footer>

</div>

</div>

<!-- Global JavaScript -->

<script
    src="<?= e($baseUrl) ?>/assets/js/dashboard.js"></script>

<!-- Module-specific JavaScript -->

<?php if (!empty($moduleScript)): ?>

<script
    src="<?= e($baseUrl . $moduleScript) ?>"></script>

<?php endif; ?>

</body>

</html>