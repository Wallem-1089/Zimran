<?php

declare(strict_types=1);

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
header('Location: create.php?visit=' . $visitId);
exit;

