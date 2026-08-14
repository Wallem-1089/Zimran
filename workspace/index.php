<?php

declare(strict_types=1);

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
    ?: 0;

if ($visitId > 0) {
    header('Location: ../modules/visits/workspace.php?id=' . $visitId);
    exit;
}

header('Location: ../modules/patients/search.php');
exit;
