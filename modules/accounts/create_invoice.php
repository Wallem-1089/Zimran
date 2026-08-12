<?php

declare(strict_types=1);

$visitId = (int)($_GET['visit'] ?? 0);
header('Location: ../billing/view.php?visit=' . $visitId);
exit;
